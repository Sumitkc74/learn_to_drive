<?php

namespace Tests\Feature;

use App\Models\DrivingSchool;
use App\Models\SchoolMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrivingSchoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_staff_can_view_only_their_school_learners(): void
    {
        $manager = User::factory()->create(['is_active' => true]);
        $student = User::factory()->create(['is_active' => true, 'name' => 'School learner']);
        $otherStudent = User::factory()->create(['is_active' => true, 'name' => 'Other learner']);
        $school = DrivingSchool::create(['name' => 'Safe Roads Academy', 'status' => 'Approved']);
        $otherSchool = DrivingSchool::create(['name' => 'Other Academy', 'status' => 'Approved']);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $manager->id, 'role' => 'Manager']);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $student->id, 'role' => 'Student']);
        SchoolMembership::create(['driving_school_id' => $otherSchool->id, 'user_id' => $otherStudent->id, 'role' => 'Student']);

        $response = $this->actingAs($manager)->get(route('learn.school.dashboard'));

        $response->assertOk()->assertSee('Safe Roads Academy')->assertSee('School learner')->assertDontSee('Other learner');
    }

    public function test_school_manager_can_enroll_an_existing_account(): void
    {
        $manager = User::factory()->create(['is_active' => true]);
        $student = User::factory()->create(['is_active' => true, 'email' => 'learner@example.com']);
        $school = DrivingSchool::create(['name' => 'Safe Roads Academy', 'status' => 'Approved']);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $manager->id, 'role' => 'Manager']);

        $this->actingAs($manager)->post(route('learn.school.enroll'), [
            'email' => $student->email,
            'role' => 'Student',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseMissing('school_memberships', ['user_id'=>$student->id]);
        $invite=\Illuminate\Support\Facades\DB::table('school_invitations')->where('user_id',$student->id)->value('id');
        $this->actingAs($student)->post(route('learn.school.invitation',$invite),['decision'=>'accept'])->assertRedirect();
        $this->assertDatabaseHas('school_memberships', [
            'driving_school_id' => $school->id,
            'user_id' => $student->id,
            'role' => 'Student',
        ]);
    }

    public function test_non_school_users_cannot_access_the_school_dashboard(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('learn.school.dashboard'))->assertForbidden();
    }
}
