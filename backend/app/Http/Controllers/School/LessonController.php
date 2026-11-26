<?php
namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\{DrivingLesson, LessonBooking, LessonVehicle, PrivateLessonRequest, User};
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
            ->with(['vehicle.school.media', 'vehicle.instructor', 'bookings' => fn ($q) => $q->where('status', 'Booked')->with('learner:id,name,email')])
            ->orderByDesc('starts_at')->paginate(20);
        $requests = PrivateLessonRequest::where('instructor_id', $request->user()->id)
            ->with(['learner:id,name,email', 'school:id,name', 'vehicle'])
            ->orderByRaw("CASE WHEN status = 'Pending' THEN 0 ELSE 1 END")->orderBy('starts_at')->take(20)->get();
        return view('school.lessons', compact('schools', 'vehicles', 'lessons', 'requests'));
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
            // Validated form values arrive as strings from a real browser;
            // Carbon 3 rejects a string here, so cast before adding.
            $end = $start->copy()->addMinutes((int) $data['duration']);
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

    private function pendingRequest(Request $request, int $privateRequest): PrivateLessonRequest
    {
        $record = PrivateLessonRequest::lockForUpdate()->findOrFail($privateRequest);
        abort_unless($record->instructor_id === $request->user()->id, 403, __('Only the addressed instructor can respond to this request.'));
        if ($record->status !== 'Pending') {
            throw ValidationException::withMessages(['request' => __('This request is no longer pending.')]);
        }
        return $record;
    }

    public function acceptRequest(Request $request, int $privateRequest)
    {
        $data = $request->validate(['price_npr' => ['required', 'integer', 'min:0', 'max:100000']]);
        DB::transaction(function () use ($request, $data, $privateRequest) {
            $record = $this->pendingRequest($request, $privateRequest);
            abort_unless($this->schools($request)->contains('driving_school_id', $record->driving_school_id), 403);
            $start = $record->starts_at->copy()->utc();
            // Validated form values arrive as strings from a real browser;
            // Carbon 3 rejects a string here, so cast before adding.
            $end = $start->copy()->addMinutes((int) $record->duration_minutes);
            if (!$start->isFuture()) {
                throw ValidationException::withMessages(['starts_at' => __('Choose a future time within the next year.')]);
            }
            if (DrivingLesson::where('cancelled', false)->whereHas('vehicle', fn ($q) => $q->where('instructor_id', $request->user()->id))
                    ->overlapping($start, $end)->exists()) {
                throw ValidationException::withMessages(['starts_at' => __('You already have a lesson at this time.')]);
            }
            if (LessonBooking::where('learner_id', $record->learner_id)->where('status', 'Booked')
                    ->whereHas('lesson', fn ($q) => $q->overlapping($start, $end))->exists()
                || DB::table('school_class_sessions as s')->join('school_batch_members as m', 'm.school_batch_id', '=', 's.school_batch_id')
                    ->where('m.user_id', $record->learner_id)->where('s.cancelled', false)->where('s.starts_at', '<', $end)->where('s.ends_at', '>', $start)->exists()) {
                throw ValidationException::withMessages(['starts_at' => __('The learner already has a booking at this time.')]);
            }
            $lesson = DrivingLesson::create(['lesson_vehicle_id' => $record->lesson_vehicle_id, 'starts_at' => $start, 'ends_at' => $end,
                'price_npr' => (int) $data['price_npr'], 'meeting_point' => $record->meeting_point]);
            LessonBooking::create(['driving_lesson_id' => $lesson->id, 'active_lesson_id' => $lesson->id, 'learner_id' => $record->learner_id]);
            $record->update(['status' => 'Accepted', 'driving_lesson_id' => $lesson->id]);
            \App\Services\SchoolNotifications::send($record->learner_id, 'Your private session request was accepted. View My driving bookings.');
            \App\Services\SchoolNotifications::send($record->instructor_id, 'You accepted a private session request.');
        });
        return back()->with('success', __('Private session request accepted.'));
    }

    public function declineRequest(Request $request, int $privateRequest)
    {
        $data = $request->validate(['decline_reason' => ['nullable', 'string', 'max:500']]);
        DB::transaction(function () use ($request, $data, $privateRequest) {
            $record = $this->pendingRequest($request, $privateRequest);
            $record->update(['status' => 'Declined', 'decline_reason' => $data['decline_reason'] ?? null]);
            \App\Services\SchoolNotifications::send($record->learner_id, 'Your private session request was declined.');
        });
        return back()->with('success', __('Private session request declined.'));
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
