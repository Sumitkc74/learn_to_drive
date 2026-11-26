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
        $data = $request->validate([
            'school' => ['nullable', 'integer', 'min:1'], 'instructor' => ['nullable', 'integer', 'min:1'],
            'vehicle' => ['nullable', 'integer', 'min:1'], 'day' => ['nullable', 'date_format:Y-m-d'],
            'time' => ['nullable', 'date_format:H:i'], 'duration' => ['nullable', 'integer', 'min:15', 'max:180'],
            'meeting_point' => ['nullable', 'string', 'max:255'],
        ]);
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
        $vehicle = isset($data['vehicle']) ? $vehicles->firstWhere('id', (int) $data['vehicle']) : null;
        $vehicle ??= $vehicles->first();
        // Tap-first picker data. Day chips cover the next 7 days; time chips
        // are the instructor's usual start times taken from their upcoming
        // available lessons (clock times, so any chosen day works), falling
        // back to common daytime slots when the instructor has no lessons
        // yet. Past times are dropped when the chosen day is today.
        $dayChips = collect(range(0, 6))->map(fn ($i) => now()->timezone('Asia/Kathmandu')->addDays($i)->toDateString())->all();
        $day = ($data['day'] ?? null) && Carbon::parse($data['day'], 'Asia/Kathmandu')->toDateString() >= now()->timezone('Asia/Kathmandu')->toDateString() ? $data['day'] : null;
        $timeChips = [];
        if ($instructor) {
            $timeChips = DrivingLesson::availableProvider()->where('cancelled', false)->where('starts_at', '>', now())
                ->whereDoesntHave('bookings', fn ($b) => $b->where('status', 'Booked'))
                ->whereHas('vehicle', fn ($v) => $v->where('instructor_id', $instructor->id)
                    ->when($school, fn ($q) => $q->where('driving_school_id', $school->id)))
                ->orderBy('starts_at')->take(30)->pluck('starts_at')
                ->map(fn ($s) => $s->copy()->timezone('Asia/Kathmandu')->format('H:i'))->unique()->sort()->values()->all();
        }
        if (empty($timeChips)) $timeChips = ['09:00', '11:00', '13:00', '15:00', '17:00'];
        if ($day === now()->timezone('Asia/Kathmandu')->toDateString()) {
            $nowTime = now()->timezone('Asia/Kathmandu')->format('H:i');
            $timeChips = array_values(array_filter($timeChips, fn ($t) => $t > $nowTime));
        }
        $time = $data['time'] ?? null;
        $duration = (int) ($data['duration'] ?? 60);
        // A time chip must be a time the instructor is actually free:
        // drop any start time where the proposed slot would overlap one
        // of their lessons, class sessions, or pending private requests
        // — the same overlap test store() applies, so a chip never leads
        // to a rejected request. Only the chosen day can be tested, so
        // without a day the chips stay as they are. If every derived
        // time is busy that day, fall back to the usual daytime slots,
        // filtered the same way.
        if ($instructor && $day) {
            $isFree = fn (string $t) => $this->instructorFreeAt($instructor, $day, $t, $duration);
            $timeChips = array_values(array_filter($timeChips, $isFree));
            if (empty($timeChips)) {
                $timeChips = array_values(array_filter(['09:00', '11:00', '13:00', '15:00', '17:00'], $isFree));
            }
            if ($day === now()->timezone('Asia/Kathmandu')->toDateString()) {
                $nowTime = now()->timezone('Asia/Kathmandu')->format('H:i');
                $timeChips = array_values(array_filter($timeChips, fn ($t) => $t > $nowTime));
            }
            // A time picked before the day was chosen may now be busy:
            // clear it so the summary cannot offer a slot the request
            // would reject.
            if ($time && !$isFree($time)) $time = null;
        }
        // Meeting-point presets: the school's address first, then the
        // instructor's most recent lesson meeting points.
        $meetingPresets = collect();
        if ($school?->address) $meetingPresets->push($school->address);
        if ($instructor) {
            $meetingPresets = $meetingPresets->merge(DrivingLesson::whereHas('vehicle', fn ($v) => $v->where('instructor_id', $instructor->id))
                ->where('cancelled', false)->orderByDesc('starts_at')->take(20)->pluck('meeting_point')->unique()->filter());
        }
        $meetingPresets = $meetingPresets->unique()->take(4)->values();
        $meetingPoint = $data['meeting_point'] ?? null;
        if (!$meetingPoint && $school?->address) $meetingPoint = $school->address;
        $canSubmit = $school && $instructor && $vehicle && $day && $time && $meetingPoint;
        $myRequests = $request->user() ? PrivateLessonRequest::where('learner_id', $request->user()->id)
            ->with(['school:id,name', 'instructor:id,name', 'vehicle', 'lesson'])->latest('id')->paginate(15) : null;
        return view('learner.private-lessons', compact('schools', 'school', 'instructors', 'instructor', 'vehicles', 'vehicle',
            'dayChips', 'day', 'timeChips', 'time', 'duration', 'meetingPresets', 'meetingPoint', 'canSubmit', 'myRequests'));
    }

    // True when the instructor has no lesson, class session, or pending
    // private request overlapping the proposed slot — the same three
    // overlap checks store() applies before accepting a request.
    private function instructorFreeAt(User $instructor, string $day, string $time, int $duration): bool
    {
        $start = Carbon::parse($day.' '.$time, 'Asia/Kathmandu')->second(0)->utc();
        $end = $start->copy()->addMinutes($duration);
        if (DrivingLesson::where('cancelled', false)->whereHas('vehicle', fn ($q) => $q->where('instructor_id', $instructor->id))
                ->overlapping($start, $end)->exists()) return false;
        if (DB::table('school_class_sessions as s')->join('school_batches as b', 'b.id', '=', 's.school_batch_id')
                ->where('b.instructor_id', $instructor->id)->where('s.cancelled', false)->where('s.starts_at', '<', $end)->where('s.ends_at', '>', $start)->exists()) return false;
        return ! PrivateLessonRequest::where('instructor_id', $instructor->id)->where('status', 'Pending')->get()
            ->contains(fn ($r) => $r->starts_at < $end && $r->endsAt() > $start);
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
                throw ValidationException::withMessages(['starts_at' => __('This instructor already has a private session request at this time.')]);
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
            \App\Services\SchoolNotifications::send($instructor->id, 'A learner requested a private session. Check your lesson schedule.');
            return $record;
        });
        return redirect()->route('learn.private-lessons')->with('success', __('Request sent.'));
    }

    public function cancel(Request $request, int $privateRequest)
    {
        $record = PrivateLessonRequest::where('learner_id', $request->user()->id)->findOrFail($privateRequest);
        abort_unless($record->status === 'Pending', 422);
        $record->update(['status' => 'Cancelled']);
        return back()->with('success', __('Private session request cancelled.'));
    }
}
