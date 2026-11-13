<?php

namespace Tests\Feature;

use App\Models\{DrivingSchool, SchoolMembership, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'Manager', string $status = 'Approved'): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $school = DrivingSchool::create(['name' => 'Road School', 'status' => $status]);
        $membership = SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $user->id, 'role' => $role]);
        return [$user, $school, $membership];
    }

    public function test_admin_creates_pending_school_with_owner_then_approves_and_suspends(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);
        $owner = User::factory()->create(['is_active' => true]);
        $this->actingAs($admin)->post(route('admin.schools.store'), [
            'name' => 'New Academy', 'owner_email' => $owner->email, 'status' => 'Approved',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $school = DrivingSchool::where('name', 'New Academy')->firstOrFail();
        $this->assertSame('Pending', $school->status);
        $this->assertDatabaseHas('school_memberships', ['driving_school_id' => $school->id, 'user_id' => $owner->id, 'role' => 'Owner']);
        $this->get(route('admin.schools.index'))->assertOk()->assertSee('New Academy');
        $this->get(route('admin.schools.edit', $school))->assertOk()->assertSee($owner->email);
        $this->patch(route('admin.schools.update', $school), ['name' => $school->name, 'status' => 'Approved'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->get(route('learn.school.dashboard'))->assertOk();
        $this->actingAs($admin)->patch(route('admin.schools.update', $school), ['name' => $school->name, 'status' => 'Suspended'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->get(route('learn.school.dashboard'))->assertForbidden();
        $this->post(route('learn.school.enroll'), ['email' => $owner->email, 'role' => 'Student'])->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['event' => 'school.updated', 'subject_id' => $school->id]);
    }

    public function test_non_admin_cannot_manage_school_approval(): void
    {
        [$owner, $school] = $this->staff('Owner');
        $this->actingAs($owner)->get(route('admin.schools.index'))->assertForbidden();
        $this->post(route('admin.schools.store'), ['name' => 'Unapproved'])->assertForbidden();
        $this->patch(route('admin.schools.update', $school), ['name' => 'Changed', 'status' => 'Approved'])->assertForbidden();
    }

    public function test_admin_email_cannot_be_owner_and_invalid_creation_is_atomic(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.schools.store'), ['name' => 'Invalid', 'owner_email' => $admin->email])->assertSessionHasErrors('owner_email');
        $this->assertDatabaseMissing('driving_schools', ['name' => 'Invalid']);
    }

    public function test_admin_can_create_ownerless_school_and_approve_it(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.schools.store'), ['name' => 'No owner yet'])->assertSessionHasNoErrors();
        $school = DrivingSchool::where('name', 'No owner yet')->firstOrFail();
        $this->assertSame('Pending', $school->status);
        $this->assertSame(0, $school->memberships()->count());
        $this->patch(route('admin.schools.update', $school), ['name' => $school->name, 'status' => 'Approved'])->assertSessionHasNoErrors();
        $this->assertSame('Approved', $school->fresh()->status);
    }

    public function test_admin_can_assign_owner_to_ownerless_school(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);
        $owner = User::factory()->create(['is_active' => true]);
        $school = DrivingSchool::create(['name' => 'No owner yet', 'status' => 'Pending']);
        $this->actingAs($admin)->patch(route('admin.schools.update', $school), ['name' => $school->name, 'status' => 'Pending', 'owner_email' => $owner->email])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('school_memberships', ['driving_school_id' => $school->id, 'user_id' => $owner->id, 'role' => 'Owner']);
        $this->assertSame('Pending', $school->fresh()->status);
    }

    public function test_assigning_a_second_owner_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);
        [$owner, $school] = $this->staff('Owner');
        $other = User::factory()->create(['is_active' => true]);
        $this->actingAs($admin)->patch(route('admin.schools.update', $school), ['name' => $school->name, 'status' => $school->status, 'owner_email' => $other->email])->assertSessionHasErrors('owner_email');
        $this->assertSame(1, $school->memberships()->where('role', 'Owner')->count());
        $this->assertDatabaseMissing('school_memberships', ['driving_school_id' => $school->id, 'user_id' => $other->id]);
    }

    public function test_enrollment_cannot_downgrade_members_or_create_managers_for_manager(): void
    {
        [$manager, $school, $membership] = $this->staff();
        $newUser = User::factory()->create(['is_active' => true]);
        $this->actingAs($manager)->post(route('learn.school.enroll'), ['email' => $manager->email, 'role' => 'Student'])->assertSessionHasErrors('email');
        $this->assertSame('Manager', $membership->fresh()->role);
        $this->post(route('learn.school.enroll'), ['email' => $newUser->email, 'role' => 'Manager'])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('school_memberships', ['user_id' => $newUser->id]);
    }

    public function test_instructor_is_read_only_and_foreign_school_is_not_accessible(): void
    {
        [$instructor, $school] = $this->staff('Instructor');
        [$other, $otherSchool, $otherMembership] = $this->staff();
        $this->actingAs($instructor)->get(route('learn.school.dashboard'))->assertOk()->assertDontSee('name="email"', false)->assertDontSee('Confirm removal');
        $this->post(route('learn.school.enroll'), ['email' => $other->email, 'role' => 'Student'])->assertForbidden();
        $this->delete(route('learn.school.members.remove', $otherMembership))->assertNotFound();
        $this->get(route('learn.school.dashboard', ['school_id' => $otherSchool->id]))->assertNotFound();
        $this->post(route('learn.school.enroll'), ['school_id' => $otherSchool->id, 'email' => $other->email, 'role' => 'Student'])->assertNotFound();
    }

    public function test_selected_school_is_preserved_and_owner_can_add_manager(): void
    {
        [$owner, $first] = $this->staff('Owner');
        $second = DrivingSchool::create(['name' => 'Second School', 'status' => 'Approved']);
        SchoolMembership::create(['driving_school_id' => $second->id, 'user_id' => $owner->id, 'role' => 'Owner']);
        $newUser = User::factory()->create(['is_active' => true]);
        $this->actingAs($owner)->get(route('learn.school.dashboard', ['school_id' => $second->id]))->assertOk()->assertSee('Choose school')->assertSee('name="school_id" value="'.$second->id.'"', false);
        $this->post(route('learn.school.enroll'), ['school_id' => $second->id, 'email' => $newUser->email, 'role' => 'Manager'])
            ->assertRedirect(route('learn.school.dashboard', ['school_id' => $second->id]));
        $this->assertDatabaseMissing('school_memberships', ['user_id'=>$newUser->id]);
        $invite=\Illuminate\Support\Facades\DB::table('school_invitations')->where('user_id',$newUser->id)->value('id');
        $this->actingAs($newUser)->post(route('learn.school.invitation',$invite),['decision'=>'accept'])->assertRedirect();
        $this->assertDatabaseHas('school_memberships', ['driving_school_id' => $second->id, 'user_id' => $newUser->id, 'role' => 'Manager']);
        $this->assertDatabaseMissing('school_memberships', ['driving_school_id' => $first->id, 'user_id' => $newUser->id]);
    }

    public function test_removal_is_scoped_and_preserves_account_and_owner(): void
    {
        [$manager, $school, $managerMembership] = $this->staff();
        [$other, $foreign, $foreignMembership] = $this->staff();
        $student = User::factory()->create(['is_active' => true]);
        $owner = User::factory()->create(['is_active' => true]);
        $ownerMembership = $school->memberships()->create(['user_id' => $owner->id, 'role' => 'Owner']);
        $studentMembership = $school->memberships()->create(['user_id' => $student->id, 'role' => 'Student']);
        $this->actingAs($manager)->delete(route('learn.school.members.remove', $foreignMembership))->assertNotFound();
        $this->delete(route('learn.school.members.remove', $ownerMembership))->assertForbidden();
        $this->delete(route('learn.school.members.remove', $managerMembership))->assertForbidden();
        $this->delete(route('learn.school.members.remove', $studentMembership))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('school_memberships', ['id' => $studentMembership->id]);
        $this->assertDatabaseHas('users', ['id' => $student->id]);
        $this->assertDatabaseHas('school_memberships', ['id' => $ownerMembership->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'school.member_removed', 'subject_id' => $school->id]);
    }

    public function test_pending_school_and_inactive_staff_are_blocked_and_nepali_navigation_works(): void
    {
        [$owner, $school] = $this->staff('Owner', 'Pending');
        $this->actingAs($owner)->get(route('learn.school.dashboard'))->assertForbidden();
        $school->update(['status' => 'Approved']);
        $this->withSession(['learner_locale' => 'ne'])->get(route('learn.school.dashboard'))
            ->assertOk()->assertSee('मेरो स्कूल')->assertSee('Learn to Drive');
        $owner->update(['is_active' => false]);
        $this->actingAs($owner)->get(route('learn.school.dashboard'))->assertRedirect(route('learn.login'));
    }
}
