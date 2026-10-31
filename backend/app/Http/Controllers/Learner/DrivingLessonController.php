<?php
namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\{DrivingLesson, LessonBooking, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DrivingLessonController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['transmission' => ['nullable', Rule::in(['Manual', 'Automatic'])],
            'date' => ['nullable', 'date_format:Y-m-d'], 'q' => ['nullable', 'string', 'max:100'],
            'school' => ['nullable', 'integer', 'min:1'], 'availability'=>['nullable','in:available,booked']]);
        $school = isset($data['school']) ? \App\Models\DrivingSchool::where('status', 'Approved')->findOrFail($data['school']) : null;
        $lessons = DrivingLesson::availableProvider()->where('cancelled', false)->where('starts_at', '>', now())
            ->when(($data['availability']??'available')==='booked',fn($q)=>$q->whereHas('bookings',fn($b)=>$b->where('status','Booked')),fn($q)=>$q->whereDoesntHave('bookings',fn($b)=>$b->where('status','Booked')))
            ->when($school, fn ($q) => $q->whereHas('vehicle', fn ($v) => $v->where('driving_school_id', $school->id)))
            ->when($data['transmission'] ?? null, fn ($q, $type) => $q->whereHas('vehicle', fn ($v) => $v->where('transmission', $type)))
            ->when($data['q'] ?? null, fn ($q, $search) => $q->whereHas('vehicle', fn ($v) => $v
                ->where('name', 'like', '%'.$search.'%')->orWhereHas('instructor', fn ($u) => $u->where('name', 'like', '%'.$search.'%'))
                ->orWhereHas('school', fn ($s) => $s->where('name', 'like', '%'.$search.'%'))))
            ->when($data['date'] ?? null, function ($q, $date) {
                $start = \Carbon\Carbon::parse($date, 'Asia/Kathmandu')->startOfDay()->utc();
                $q->where('starts_at', '>=', $start)->where('starts_at', '<', $start->copy()->addDay());
            })->with(['vehicle.school', 'vehicle.instructor'])->orderBy('starts_at')->paginate(12)->withQueryString();
        $waiting=$request->user()?\Illuminate\Support\Facades\DB::table('lesson_waitlist')->where('user_id',$request->user()->id)->whereIn('driving_lesson_id',$lessons->pluck('id'))->pluck('status','driving_lesson_id'):collect();
        return view('learner.lessons.index', compact('lessons', 'school','waiting'));
    }

    public function bookings(Request $request)
    {
        $bookings = LessonBooking::where('learner_id', $request->user()->id)
            ->withExists(['lesson as provider_available' => fn ($q) => $q->availableProvider()])
            ->with(['lesson.vehicle.school', 'lesson.vehicle.instructor', 'payment'])->latest('id')->paginate(15);
        return view('learner.lessons.bookings', compact('bookings'));
    }

    public function book(Request $request, int $lesson)
    {
        DB::transaction(function () use ($request, $lesson) {
            // Serialize this learner's requests, then claim the unique lesson slot.
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $slot = DrivingLesson::availableProvider()->lockForUpdate()->findOrFail($lesson);
            abort_if($slot->vehicle->instructor_id === $request->user()->id, 403);
            if ($slot->cancelled || !$slot->starts_at->isFuture() || $slot->bookings()->where('status', 'Booked')->exists()) {
                throw ValidationException::withMessages(['lesson' => __('This lesson is no longer available.')]);
            }
            if (LessonBooking::where('learner_id', $request->user()->id)->where('status', 'Booked')
                ->whereHas('lesson', fn ($q) => $q->overlapping($slot->starts_at, $slot->ends_at))->exists()) {
                throw ValidationException::withMessages(['lesson' => __('You already have a booking at this time.')]);
            }
            if(DB::table('school_class_sessions as s')->join('school_batch_members as m','m.school_batch_id','=','s.school_batch_id')
                ->where('m.user_id',$request->user()->id)->where('s.cancelled',false)->where('s.starts_at','<',$slot->ends_at)->where('s.ends_at','>',$slot->starts_at)->exists()) {
                throw ValidationException::withMessages(['lesson'=>__('You already have a booking at this time.')]);
            }
            DB::table('lesson_waitlist')->where('driving_lesson_id',$slot->id)->where('user_id',$request->user()->id)->delete();
            LessonBooking::create(['driving_lesson_id' => $slot->id, 'active_lesson_id' => $slot->id, 'learner_id' => $request->user()->id]);
            \App\Services\SchoolNotifications::send($slot->vehicle->instructor_id, 'A learner booked your driving lesson. Check your lesson schedule.');
            \App\Services\SchoolNotifications::send($request->user()->id, 'Your driving lesson is booked. View My driving bookings.');
        });
        return redirect()->route('learn.lessons.bookings')->with('success', __('Driving lesson booked.'));
    }

    public function cancel(Request $request, int $booking)
    {
        $record = LessonBooking::where('learner_id', $request->user()->id)->findOrFail($booking);
        DB::transaction(function () use ($record) {
            $slot = DrivingLesson::whereKey($record->driving_lesson_id)->lockForUpdate()->firstOrFail();
            abort_unless($slot->starts_at->isFuture(), 422);
            $record=LessonBooking::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->driving_lesson_id===$slot->id,409);
            if($record->status!=='Booked')return;
            $record->update(['status' => 'Cancelled', 'active_lesson_id' => null]);
            \App\Models\SchoolLessonPayment::where('lesson_booking_id',$record->id)->where('status','Paid')->update(['status'=>'RefundRequested']);
            \App\Services\LessonWaitlist::released($slot->id);
            \App\Services\SchoolNotifications::send($slot->vehicle->instructor_id, 'A learner cancelled their driving lesson booking.');
        });
        return back()->with('success', __('Booking cancelled.'));
    }
}
