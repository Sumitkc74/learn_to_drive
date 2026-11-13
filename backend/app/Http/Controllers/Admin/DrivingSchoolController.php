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
        $data = $request->validate($this->rules() + ['owner_email' => ['nullable', 'email', 'max:255']]);
        $school = DB::transaction(function () use ($data, $request) {
            // The owner is optional: an admin-curated listing (e.g. a real
            // school found on Google Maps) starts Pending with no owner, and
            // an owner can be attached later when the school joins.
            $owner = null;
            if (!empty($data['owner_email'])) {
                $owner = User::where('email', $data['owner_email'])->where('is_active', true)
                    ->where('role', '!=', 'Admin')->lockForUpdate()->first();
                if (!$owner) {
                    throw ValidationException::withMessages(['owner_email' => __('Choose an existing active non-admin account.')]);
                }
            }
            unset($data['owner_email']);
            $school = DrivingSchool::create($data + ['status' => 'Pending']);
            if ($owner) {
                SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $owner->id, 'role' => 'Owner']);
            }
            $this->audit($request, $school, 'school.created', [], ['status' => 'Pending', 'owner_id' => $owner?->id]);
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
        $data = $request->validate($this->rules() + ['status' => ['required', Rule::in(['Pending', 'Approved', 'Suspended'])], 'owner_email' => ['nullable', 'email', 'max:255']]);
        DB::transaction(function () use ($request, $school, $data) {
            $school = DrivingSchool::whereKey($school->id)->lockForUpdate()->firstOrFail();
            // An owner can be attached later to an ownerless school. A school
            // that already has an owner keeps it — ownership changes go
            // through the transfer flow, not this form.
            $owner = null;
            if (!empty($data['owner_email'])) {
                $owner = User::where('email', $data['owner_email'])->where('is_active', true)
                    ->where('role', '!=', 'Admin')->lockForUpdate()->first();
                if (!$owner) {
                    throw ValidationException::withMessages(['owner_email' => __('Choose an existing active non-admin account.')]);
                }
                $existingOwner = $school->memberships()->where('role', 'Owner')->first();
                if ($existingOwner && $existingOwner->user_id !== $owner->id) {
                    throw ValidationException::withMessages(['owner_email' => __('This school already has an owner. Use ownership transfer to change it.')]);
                }
            }
            $after = $data;
            unset($after['owner_email']);
            $before = $school->only(array_keys($after));
            $school->update($after);
            if ($owner && !$school->memberships()->where('role', 'Owner')->where('user_id', $owner->id)->exists()) {
                SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $owner->id, 'role' => 'Owner']);
                $after['owner_id'] = $owner->id;
            }
            $this->audit($request, $school, 'school.updated', $before, $after);
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
