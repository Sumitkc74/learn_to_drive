<?php
namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\{DrivingLesson, LessonVehicle, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LessonController extends Controller
{
    private function schools(Request $request)
    {
        return $request->user()->schoolMemberships()->where('role', 'Instructor')
            ->whereHas('school', fn ($q) => $q->where('status', 'Approved'))->with('school')->get();
    }

    public function index(Request $request)
    {
        $schools = $this->schools($request);
        abort_if($schools->isEmpty(), 403);
        $vehicles = LessonVehicle::where('instructor_id', $request->user()->id)->whereIn('driving_school_id', $schools->pluck('driving_school_id'))->with('school')->get();
        $lessons = DrivingLesson::whereHas('vehicle', fn ($q) => $q->where('instructor_id', $request->user()->id))
            ->with(['vehicle.school', 'vehicle.instructor', 'bookings' => fn ($q) => $q->where('status', 'Booked')->with('learner:id,name,email')])
            ->orderByDesc('starts_at')->paginate(20);
        return view('school.lessons', compact('schools', 'vehicles', 'lessons'));
    }

    public function vehicle(Request $request)
    {
        $data = $request->validate(['driving_school_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:100'],
            'registration' => ['required', 'string', 'max:40'], 'transmission' => ['required', Rule::in(['Manual', 'Automatic'])]]);
        abort_unless($this->schools($request)->contains('driving_school_id', $data['driving_school_id']), 403);
        $data['registration'] = mb_strtoupper(preg_replace('/\s+/u', '', $data['registration']));
        if ($data['registration'] === '' || LessonVehicle::where('registration', $data['registration'])->exists()) {
            throw ValidationException::withMessages(['registration' => __('This vehicle is already registered or its registration is invalid.')]);
        }
        LessonVehicle::create($data + ['instructor_id' => $request->user()->id]);
        return back()->with('success', __('Vehicle added.'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['lesson_vehicle_id' => ['required', 'integer'], 'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'duration' => ['required', 'integer', 'min:15', 'max:180'], 'price_npr' => ['required', 'integer', 'min:0', 'max:100000'],
            'meeting_point' => ['required', 'string', 'max:255']]);
        DB::transaction(function () use ($request, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $vehicle = LessonVehicle::where('instructor_id', $request->user()->id)
                ->whereIn('driving_school_id', $this->schools($request)->pluck('driving_school_id'))->findOrFail($data['lesson_vehicle_id']);
            $start = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $data['starts_at'], 'Asia/Kathmandu')->second(0)->utc();
            $end = $start->copy()->addMinutes($data['duration']);
            if (!$start->isFuture() || $start->greaterThan(now()->addYear())) {
                throw ValidationException::withMessages(['starts_at' => __('Choose a future time within the next year.')]);
            }
            if (DrivingLesson::where('cancelled', false)->whereHas('vehicle', fn ($q) => $q->where('instructor_id', $request->user()->id))
                ->overlapping($start, $end)->exists() || DB::table('school_class_sessions as s')->join('school_batches as b','b.id','=','s.school_batch_id')
                    ->where('b.instructor_id',$request->user()->id)->where('s.cancelled',false)->where('s.starts_at','<',$end)->where('s.ends_at','>',$start)->exists()) {
                throw ValidationException::withMessages(['starts_at' => __('You already have a lesson at this time.')]);
            }
            DrivingLesson::create(['lesson_vehicle_id' => $vehicle->id, 'starts_at' => $start, 'ends_at' => $end,
                'price_npr' => $data['price_npr'], 'meeting_point' => $data['meeting_point']]);
        });
        return back()->with('success', __('Lesson time published.'));
    }

    public function cancel(Request $request, int $lesson)
    {
        DB::transaction(function () use ($request, $lesson) {
            $slot = DrivingLesson::whereHas('vehicle', fn ($q) => $q->where('instructor_id', $request->user()->id))->lockForUpdate()->findOrFail($lesson);
            abort_unless($slot->starts_at->isFuture(), 422);
            $slot->update(['cancelled' => true]);
            foreach(DB::table('lesson_waitlist')->where('driving_lesson_id',$slot->id)->whereIn('status',['Waiting','Notified'])->get() as $entry)
                \App\Services\SchoolNotifications::send($entry->user_id,'A lesson on your waitlist was cancelled by the instructor.');
            DB::table('lesson_waitlist')->where('driving_lesson_id',$slot->id)->update(['status'=>'Cancelled','updated_at'=>now()]);
            $bookings=$slot->bookings()->where('status','Booked')->lockForUpdate()->get();
            foreach($bookings as $booking){
                \App\Models\SchoolLessonPayment::where('lesson_booking_id',$booking->id)->where('status','Paid')->update(['status'=>'RefundRequested']);
                \App\Services\SchoolNotifications::send($booking->learner_id,'Your instructor cancelled a driving lesson. Check your booking and any refund status.');
            }
            $slot->bookings()->where('status', 'Booked')->update(['status' => 'Cancelled', 'active_lesson_id' => null]);
        });
        return back()->with('success', __('Lesson cancelled. Affected bookings are marked cancelled.'));
    }
}
