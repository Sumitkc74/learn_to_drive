<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, DrivingLesson, DrivingSchool, LessonBooking, LessonVehicle, SchoolJoinRequest, SchoolLessonPayment, SchoolMembership, User};
use App\Support\SchoolMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DrivingSchoolController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        if (is_string($status)) {
            // The status filter is case-insensitive (?status=approved):
            // normalize to the stored case before validating, so a
            // lowercase value filters instead of erroring with
            // "The selected status is invalid."
            $request->merge(['status' => ucfirst(strtolower($status))]);
        }
        $data = $request->validate(['status' => ['nullable', Rule::in(['Pending', 'Approved', 'Suspended'])], 'search' => ['nullable', 'string', 'max:100']]);
        $schools = DrivingSchool::withCount('memberships')
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($data['search'] ?? null, fn ($q, $search) => $q->where(fn ($b) => $b->where('name', 'like', '%'.$search.'%')->orWhere('address', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')->when(ctype_digit($search), fn ($c) => $c->orWhere('driving_schools.id', $search))))
            ->latest('id')->paginate(20)->withQueryString();

        return view('admin.schools.index', compact('schools'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + ['owner_email' => ['nullable', 'email', 'max:255'], 'logo' => SchoolMedia::logoRules()] + SchoolMedia::galleryRules());
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
            SchoolMedia::attachLogo($school, $request->file('logo'));
            SchoolMedia::attachGallery($school, $request->file('gallery_images', []), $request->file('gallery_videos', []));
            $this->audit($request, $school, 'school.created', [], ['status' => 'Pending', 'owner_id' => $owner?->id]);
            return $school;
        });

        return redirect()->route('admin.schools.edit', $school)->with('success', __('School created. Review its details before approval.'));
    }

    public function edit(DrivingSchool $school)
    {
        $school->load(['memberships' => fn ($q) => $q->where('role', 'Owner')->with('user:id,name,email'), 'ratesDeclinedBy:id,name', 'media']);
        return view('admin.schools.edit', compact('school'));
    }

    public function update(Request $request, DrivingSchool $school)
    {
        $data = $request->validate($this->rules() + ['status' => ['required', Rule::in(['Pending', 'Approved', 'Suspended'])], 'owner_email' => ['nullable', 'email', 'max:255'], 'logo' => SchoolMedia::logoRules(), 'remove_logo' => ['nullable', 'boolean'], 'remove_gallery' => ['nullable', 'array'], 'remove_gallery.*' => ['integer']] + SchoolMedia::galleryRules());
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
            unset($after['owner_email'], $after['logo'], $after['remove_logo'], $after['remove_gallery'], $after['gallery_images'], $after['gallery_videos']);
            $before = $school->only(array_keys($after));
            $school->update($after);
            if ($owner && !$school->memberships()->where('role', 'Owner')->where('user_id', $owner->id)->exists()) {
                SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $owner->id, 'role' => 'Owner']);
                $after['owner_id'] = $owner->id;
            }
            // Removals run before additions so the gallery cap counts the
            // items being kept, not the ones being replaced.
            SchoolMedia::removeGalleryItems($school, $data['remove_gallery'] ?? []);
            SchoolMedia::attachLogo($school, $request->file('logo'), (bool) ($data['remove_logo'] ?? false));
            SchoolMedia::attachGallery($school, $request->file('gallery_images', []), $request->file('gallery_videos', []));
            $this->audit($request, $school, 'school.updated', $before, $after);
        });

        return back()->with('success', __('School details updated.'));
    }

    public function proposeRates(Request $request, DrivingSchool $school)
    {
        // At least one rate is required; a blank field is left out of the
        // proposal and keeps the school's current agreed value on accept.
        $data = $request->validate([
            'commission_percent' => ['nullable', 'integer', 'between:0,100', 'required_without:enrollment_fee_paisa'],
            'enrollment_fee_paisa' => ['nullable', 'integer', 'min:0', 'required_without:commission_percent'],
        ]);
        DB::transaction(function () use ($request, $school, $data) {
            $school = DrivingSchool::whereKey($school->id)->lockForUpdate()->firstOrFail();
            $before = $school->only(['proposed_commission_percent', 'proposed_enrollment_fee_paisa']);
            $school->update([
                'proposed_commission_percent' => $data['commission_percent'] ?? null,
                'proposed_enrollment_fee_paisa' => $data['enrollment_fee_paisa'] ?? null,
                // A fresh proposal starts clean: any earlier decline is
                // resolved by the renegotiation, not left hanging.
                'rates_declined_at' => null,
                'rates_declined_reason' => null,
                'rates_declined_by' => null,
            ]);
            $this->audit($request, $school, 'school.rates-proposed', $before, $school->only(['proposed_commission_percent', 'proposed_enrollment_fee_paisa']));
            // The school must accept before anything changes; tell its
            // Owner and Managers the proposal is waiting in their centre.
            foreach ($school->memberships()->whereIn('role', ['Owner', 'Manager'])->pluck('user_id') as $userId) {
                \App\Services\SchoolNotifications::send($userId, 'The platform proposed new rates for '.$school->name.'. Review them in your school centre.');
            }
        });

        return back()->with('success', __('Rates proposed. The school must accept them before they apply.'));
    }

    // Two-step delete confirmation: a plain page, no JavaScript. The
    // native confirm() it replaces could not be answered by an automated
    // browser (auto-dismiss cancels the delete), so the delete form now
    // lives here and the edit page links here instead.
    public function confirmDelete(DrivingSchool $school)
    {
        return view('admin.schools.delete', compact('school'));
    }

    public function destroy(Request $request, DrivingSchool $school)
    {
        // Check and delete under one lock: several related tables cascade
        // on school delete, so a school with any related rows must be
        // refused before anything is removed.
        $blocking = DB::transaction(function () use ($request, $school) {
            $school = DrivingSchool::whereKey($school->id)->lockForUpdate()->firstOrFail();
            if ($label = $this->blockingRelation($school)) {
                return $label;
            }
            $before = $school->only(['name', 'status', 'address']);
            // Owner memberships go with the school (see blockingRelation):
            // record whose they were so the audit trail shows it.
            $ownerIds = $school->memberships()->where('role', 'Owner')->pluck('user_id')->all();
            if ($ownerIds !== []) {
                $before['owner_user_ids'] = $ownerIds;
            }
            $school->delete();
            $this->audit($request, $school, 'school.deleted', $before, []);
            return null;
        });
        if ($blocking !== null) {
            return back()->with('error', __('This school cannot be deleted because it still has :relation.', ['relation' => $blocking]));
        }

        return redirect()->route('admin.schools.index')->with('success', __('School deleted.'));
    }

    // First related record type that blocks deletion, or null when the
    // school is empty. Courses are checked before batches: a batch always
    // belongs to a course, so a school with batches is caught by the
    // courses check first and the batches check is belt-and-braces.
    //
    // Owner memberships alone do NOT block: deleting the school removes
    // them with it (school_memberships.driving_school_id cascades on
    // delete) while the user accounts themselves are untouched, and a
    // member's memberships at other schools survive. Any other member —
    // student, instructor, manager — still blocks, and the remaining
    // checks still run, so an owner-only school with vehicles, lessons,
    // bookings, payments, join requests, courses or batches is refused.
    private function blockingRelation(DrivingSchool $school): ?string
    {
        if ($school->memberships()->where('role', '!=', 'Owner')->exists()) {
            return __('members');
        }
        if (LessonVehicle::where('driving_school_id', $school->id)->exists()) {
            return __('vehicles');
        }
        if (DrivingLesson::whereHas('vehicle', fn ($q) => $q->where('driving_school_id', $school->id))->exists()) {
            return __('lessons');
        }
        if (LessonBooking::whereHas('lesson.vehicle', fn ($q) => $q->where('driving_school_id', $school->id))->exists()) {
            return __('lesson bookings');
        }
        if (SchoolLessonPayment::whereHas('booking.lesson.vehicle', fn ($q) => $q->where('driving_school_id', $school->id))->exists()) {
            return __('lesson payments');
        }
        if (SchoolJoinRequest::where('driving_school_id', $school->id)->exists()) {
            return __('join requests');
        }
        if (DB::table('school_courses')->where('driving_school_id', $school->id)->exists()) {
            return __('courses');
        }
        if (DB::table('school_batches')->join('school_courses', 'school_batches.school_course_id', '=', 'school_courses.id')->where('school_courses.driving_school_id', $school->id)->exists()) {
            return __('batches');
        }

        return null;
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
