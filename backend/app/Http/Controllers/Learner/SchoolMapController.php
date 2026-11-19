<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\{DrivingLesson, DrivingSchool, SchoolJoinRequest};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SchoolMapController extends Controller
{
    // Upcoming lessons shown inside each school's dialog. Bounded at 5
    // per school so the page never loads every lesson for every school.
    public const DIALOG_LESSONS_PER_SCHOOL = 5;

    public function index(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $search = trim($data['q'] ?? '');
        $schools = DrivingSchool::where('status', 'Approved')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$search.'%')->orWhere('address', 'like', '%'.$search.'%')))
            ->with('media')
            ->orderBy('name')->orderBy('id')->paginate(24, ['id', 'name', 'address', 'phone', 'email', 'latitude', 'longitude'])
            ->withQueryString();
        $memberSchoolIds = $request->user()?->schoolMemberships()->pluck('driving_school_id')->all() ?? [];

        // One query for the bookable lessons of every school on this page,
        // grouped by school and capped per school. Same availability rules
        // as the lessons index: approved provider, future, not cancelled,
        // no confirmed booking.
        $upcomingLessons = DrivingLesson::availableProvider()
            ->where('cancelled', false)->where('starts_at', '>', now())
            ->whereDoesntHave('bookings', fn ($b) => $b->where('status', 'Booked'))
            ->whereHas('vehicle', fn ($v) => $v->whereIn('driving_school_id', $schools->pluck('id')))
            ->with(['vehicle.school.media', 'vehicle.instructor'])
            ->orderBy('starts_at')->take(self::DIALOG_LESSONS_PER_SCHOOL * max(1, $schools->count()))
            ->get()->groupBy(fn ($lesson) => $lesson->vehicle->driving_school_id)
            ->map(fn ($lessons) => $lessons->take(self::DIALOG_LESSONS_PER_SCHOOL));

        return response()->view('learner.schools', compact('schools', 'search', 'memberSchoolIds', 'upcomingLessons'))
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function joinRequest(Request $request, int $school)
    {
        $school = DrivingSchool::whereKey($school)->where('status', 'Approved')->firstOrFail();
        $user = $request->user();
        if ($user->schoolMemberships()->where('driving_school_id', $school->id)->exists())
            throw ValidationException::withMessages(['school' => __('You are already a member of this school.')]);
        if (SchoolJoinRequest::where('driving_school_id', $school->id)->where('user_id', $user->id)->where('status', 'Pending')->exists())
            throw ValidationException::withMessages(['school' => __('You already have a pending request to join this school.')]);
        SchoolJoinRequest::create(['driving_school_id' => $school->id, 'user_id' => $user->id, 'status' => 'Pending']);
        return $request->expectsJson()
            ? response()->json(['message' => __('Join request sent. The school will review it.')])
            : back()->with('success', __('Join request sent. The school will review it.'));
    }
}
