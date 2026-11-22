<?php
namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\{DrivingLesson, DrivingSchool, LessonBooking, LessonVehicle, PrivateLessonRequest, User};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrivateLessonController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['school' => ['nullable', 'integer', 'min:1'], 'instructor' => ['nullable', 'integer', 'min:1']]);
        $schools = DrivingSchool::where('status', 'Approved')->orderBy('name')->get(['id', 'name']);
        $school = isset($data['school']) ? DrivingSchool::where('status', 'Approved')->findOrFail($data['school']) : null;
        $instructorIds = LessonVehicle::query()->when($school, fn ($q) => $q->where('driving_school_id', $school->id))->distinct()->pluck('instructor_id');
        $instructors = User::whereIn('id', $instructorIds)->where('is_active', true)
            ->whereHas('schoolMemberships', fn ($m) => $m->where('role', 'Instructor')
                ->when($school, fn ($q) => $q->where('driving_school_id', $school->id))
                ->whereHas('school', fn ($s) => $s->where('status', 'Approved')))
            ->orderBy('name')->get(['id', 'name']);
        $instructor = isset($data['instructor']) ? $instructors->firstWhere('id', (int) $data['instructor']) : null;
        $vehicles = LessonVehicle::query()->when($school, fn ($q) => $q->where('driving_school_id', $school->id))
            ->when($instructor, fn ($q) => $q->where('instructor_id', $instructor->id))
            ->with(['instructor:id,name', 'school:id,name'])->orderBy('name')->get();
        $myRequests = PrivateLessonRequest::where('learner_id', $request->user()->id)
            ->with(['school:id,name', 'instructor:id,name', 'vehicle', 'lesson'])->latest('id')->paginate(15);
        return view('learner.private-lessons', compact('schools', 'school', 'instructors', 'instructor', 'vehicles', 'myRequests'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'driving_school_id' => ['required', 'integer'],
            'instructor_id' => ['required', 'integer'],
            'lesson_vehicle_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date_format:Y-m-d\\TH:i'],
            'duration' => ['required', 'integer', 'min:15', 'max:180'],
            'meeting_point' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $record = DB::transaction(function () use ($request, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $school = DrivingSchool::where('status', 'Approved')->lockForUpdate()->findOrFail($data['driving_school_id']);
            $instructor = User::where('is_active', true)->lockForUpdate()->findOrFail($data['instructor_id']);
            abort_if($instructor->id === $request->user()->id, 403);
            if (!$instructor->schoolMemberships()->where('role', 'Instructor')->where('driving_school_id', $school->id)->exists()) {
                throw ValidationException::withMessages(['instructor_id' => __('This instructor does not teach at the chosen school.')]);
            }
            $vehicle = LessonVehicle::lockForUpdate()->findOrFail($data['lesson_vehicle_id']);
            if ($vehicle->instructor_id !== $instructor->id || $vehicle->driving_school_id !== $school->id) {
                throw ValidationException::withMessages(['lesson_vehicle_id' => __('This vehicle does not belong to the chosen instructor.')]);
            }
            $start = Carbon::createFromFormat('Y-m-d\\TH:i', $data['starts_at'], 'Asia/Kathmandu')->second(0)->utc();
            // Validated form values arrive as strings from a real browser;
            // Carbon 3 rejects a string here, so cast before adding.
            $end = $start->copy()->addMinutes((int) $data['duration']);
            if (!$start->isFuture() || $start->greaterThan(now()->addYear())) {
                throw ValidationException::withMessages(['starts_at' => __('Choose a future time within the next year.')]);
            }
            if (DrivingLesson::where('cancelled', false)->whereHas('vehicle', fn ($q) => $q->where('instructor_id', $instructor->id))
                    ->overlapping($start, $end)->exists()
                || DB::table('school_class_sessions as s')->join('school_batches as b', 'b.id', '=', 's.school_batch_id')
                    ->where('b.instructor_id', $instructor->id)->where('s.cancelled', false)->where('s.starts_at', '<', $end)->where('s.ends_at', '>', $start)->exists()) {
                throw ValidationException::withMessages(['starts_at' => __('This instructor already has a lesson at this time.')]);
            }
            if (PrivateLessonRequest::where('instructor_id', $instructor->id)->where('status', 'Pending')->get()
                    ->contains(fn ($r) => $r->starts_at < $end && $r->endsAt() > $start)) {
                throw ValidationException::withMessages(['starts_at' => __('This instructor already has a private lesson request at this time.')]);
            }
            if (LessonBooking::where('learner_id', $request->user()->id)->where('status', 'Booked')
                    ->whereHas('lesson', fn ($q) => $q->overlapping($start, $end))->exists()
                || DB::table('school_class_sessions as s')->join('school_batch_members as m', 'm.school_batch_id', '=', 's.school_batch_id')
                    ->where('m.user_id', $request->user()->id)->where('s.cancelled', false)->where('s.starts_at', '<', $end)->where('s.ends_at', '>', $start)->exists()) {
                throw ValidationException::withMessages(['starts_at' => __('You already have a booking at this time.')]);
            }
            if (PrivateLessonRequest::where('learner_id', $request->user()->id)->where('status', 'Pending')->get()
                    ->contains(fn ($r) => $r->starts_at < $end && $r->endsAt() > $start)) {
                throw ValidationException::withMessages(['starts_at' => __('You already have a pending request at this time.')]);
            }
            $record = PrivateLessonRequest::create([
                'learner_id' => $request->user()->id, 'driving_school_id' => $school->id, 'instructor_id' => $instructor->id,
                'lesson_vehicle_id' => $vehicle->id, 'starts_at' => $start, 'duration_minutes' => (int) $data['duration'],
                'meeting_point' => $data['meeting_point'], 'note' => $data['note'] ?? null,
            ]);
            \App\Services\SchoolNotifications::send($instructor->id, 'A learner requested a private driving lesson. Check your lesson schedule.');
            return $record;
        });
        return redirect()->route('learn.private-lessons')->with('success', __('Request sent.'));
    }

    public function cancel(Request $request, int $privateRequest)
    {
        $record = PrivateLessonRequest::where('learner_id', $request->user()->id)->findOrFail($privateRequest);
        abort_unless($record->status === 'Pending', 422);
        $record->update(['status' => 'Cancelled']);
        return back()->with('success', __('Private lesson request cancelled.'));
    }
}
