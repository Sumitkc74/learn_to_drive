<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\DrivingSchool;
use App\Models\SchoolMembership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $school = $this->school($request);
        $students = SchoolMembership::where('driving_school_id', $school->id)
            ->where('role', 'Student')
            ->with('user:id,name,email,created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('school.dashboard', [
            'school' => $school,
            'students' => $students,
            'staff' => $school->memberships()->whereIn('role', ['Owner', 'Manager', 'Instructor'])->with('user:id,name,email,is_active')->paginate(20, ['*'], 'staff_page')->withQueryString(),
            'schools' => $request->user()->schoolMembershipsForStaff()->whereHas('school', fn ($q) => $q->where('status', 'Approved'))->with('school')->get(),
            'membershipRole' => $this->membershipRole($request, $school),
            'studentCount' => SchoolMembership::where('driving_school_id', $school->id)->where('role', 'Student')->count(),
            'activeStaffCount' => SchoolMembership::where('driving_school_id', $school->id)->whereIn('role', ['Owner', 'Manager', 'Instructor'])->whereHas('user', fn ($q) => $q->where('is_active', true))->count(),
        ]);
    }

    public function enroll(Request $request)
    {
        $school = $this->school($request);
        return app(PartnerController::class)->action($request, $school->id, 'invite');
    }

    public function remove(Request $request, int $membership)
    {
        $school = $this->school($request);
        DB::transaction(function () use ($request, $school, $membership) {
            $school = DrivingSchool::whereKey($school->id)->lockForUpdate()->firstOrFail();
            abort_unless($school->status === 'Approved', 403);
            $role = $this->membershipRole($request, $school);
            $member = $school->memberships()->findOrFail($membership);
            $allowed = $role === 'Owner' ? ['Student', 'Instructor', 'Manager'] : ($role === 'Manager' ? ['Student', 'Instructor'] : []);
            abort_unless(in_array($member->role, $allowed, true), 403);
            $this->audit($request, $school, 'school.member_removed', $member->user_id, $member->role);
            $member->delete();
        });
        return redirect()->route('learn.school.dashboard', ['school_id' => $school->id])
            ->with('success', __('School membership removed. The learner account and learning history are unchanged.'));
    }

    private function audit(Request $request, DrivingSchool $school, string $event, int $userId, string $role): void
    {
        \App\Models\AuditLog::create(['actor_id' => $request->user()->id, 'event' => $event,
            'subject_type' => DrivingSchool::class, 'subject_id' => $school->id, 'subject_label' => $school->name,
            'new_values' => ['member_user_id' => $userId, 'role' => $role]]);
    }

    private function school(Request $request): DrivingSchool
    {
        $request->validate(['school_id' => ['nullable', 'integer', 'min:1']]);
        $schoolId = $request->integer('school_id');
        $membership = $request->user()->schoolMembershipsForStaff()
            ->whereHas('school', fn ($query) => $query->where('status', 'Approved'))
            ->when($schoolId, fn ($query) => $query->where('driving_school_id', $schoolId))
            ->with('school')
            ->firstOrFail();

        return $membership->school;
    }

    private function membershipRole(Request $request, DrivingSchool $school): string
    {
        return (string) $request->user()->schoolMemberships()->where('driving_school_id', $school->id)->value('role');
    }
}
