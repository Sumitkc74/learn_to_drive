<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, DrivingSchool, SchoolMembership, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DrivingSchoolController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['status' => ['nullable', Rule::in(['Pending', 'Approved', 'Suspended'])]]);
        $schools = DrivingSchool::withCount('memberships')
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest('id')->paginate(20)->withQueryString();

        return view('admin.schools.index', compact('schools'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + ['owner_email' => ['required', 'email', 'max:255']]);
        $school = DB::transaction(function () use ($data, $request) {
            $owner = User::where('email', $data['owner_email'])->where('is_active', true)
                ->where('role', '!=', 'Admin')->lockForUpdate()->first();
            if (!$owner) {
                throw ValidationException::withMessages(['owner_email' => __('Choose an existing active non-admin account.')]);
            }
            unset($data['owner_email']);
            $school = DrivingSchool::create($data + ['status' => 'Pending']);
            SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $owner->id, 'role' => 'Owner']);
            $this->audit($request, $school, 'school.created', [], ['status' => 'Pending', 'owner_id' => $owner->id]);
            return $school;
        });

        return redirect()->route('admin.schools.edit', $school)->with('success', __('School created. Review its details before approval.'));
    }

    public function edit(DrivingSchool $school)
    {
        $school->load(['memberships' => fn ($q) => $q->where('role', 'Owner')->with('user:id,name,email')]);
        return view('admin.schools.edit', compact('school'));
    }

    public function update(Request $request, DrivingSchool $school)
    {
        $data = $request->validate($this->rules() + ['status' => ['required', Rule::in(['Pending', 'Approved', 'Suspended'])]]);
        DB::transaction(function () use ($request, $school, $data) {
            $school = DrivingSchool::whereKey($school->id)->lockForUpdate()->firstOrFail();
            if ($data['status'] === 'Approved' && !$school->memberships()->where('role', 'Owner')
                ->whereHas('user', fn ($q) => $q->where('is_active', true)->where('role', '!=', 'Admin'))->exists()) {
                throw ValidationException::withMessages(['status' => __('An active school owner is required before approval.')]);
            }
            $before = $school->only(array_keys($data));
            $school->update($data);
            $this->audit($request, $school, 'school.updated', $before, $data);
        });

        return back()->with('success', __('School details updated.'));
    }

    private function rules(): array
    {
        return ['name' => ['required', 'string', 'max:160'], 'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180']];
    }

    private function audit(Request $request, DrivingSchool $school, string $event, array $before, array $after): void
    {
        AuditLog::create(['actor_id' => $request->user()->id, 'event' => $event,
            'subject_type' => DrivingSchool::class, 'subject_id' => $school->id, 'subject_label' => $school->name,
            'old_values' => $before, 'new_values' => $after]);
    }
}
