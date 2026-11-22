<?php
namespace Tests\Feature;

use App\Models\{DrivingLesson, DrivingSchool, LessonBooking, LessonVehicle, PrivateLessonRequest, SchoolMembership, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivateLessonRequestTest extends TestCase
{
    use RefreshDatabase;

    private function offering(): array
    {
        $teacher = User::factory()->create(['is_active' => true]);
        $school = DrivingSchool::create(['name' => 'Lesson Academy', 'status' => 'Approved']);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $teacher->id, 'role' => 'Instructor']);
        $car = LessonVehicle::create(['driving_school_id' => $school->id, 'instructor_id' => $teacher->id,
            'name' => 'Training car', 'registration' => 'PRIVATE-' . $teacher->id, 'transmission' => 'Manual']);
        return [$teacher, $school, $car];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'driving_school_id' => '1', 'instructor_id' => '1', 'lesson_vehicle_id' => '1',
            'starts_at' => now()->addDays(2)->setTime(9, 0)->format('Y-m-d\\TH:i'),
            'duration' => '60', 'meeting_point' => 'School reception', 'note' => 'First lesson, please.',
        ], $overrides);
    }

    private function requestFor(array $offering, User $learner, array $overrides = []): PrivateLessonRequest
    {
        [$teacher, $school, $car] = $offering;
        return PrivateLessonRequest::create(array_merge([
            'learner_id' => $learner->id, 'driving_school_id' => $school->id, 'instructor_id' => $teacher->id,
            'lesson_vehicle_id' => $car->id, 'starts_at' => now()->addDays(2)->setTime(9, 0),
            'duration_minutes' => 60, 'meeting_point' => 'School reception',
        ], $overrides));
    }

    public function test_guest_is_redirected_to_sign_in(): void
    {
        $this->get(route('learn.private-lessons'))->assertRedirect(route('learn.login'));
        $this->post(route('learn.private-lessons.store'), $this->payload())->assertRedirect(route('learn.login'));
    }

    public function test_learner_sees_form_with_instructors_and_vehicles(): void
    {
        [$teacher, $school, $car] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $this->actingAs($learner)->get(route('learn.private-lessons', ['school' => $school->id]))->assertOk()
            ->assertSee('Request a private lesson')->assertSee($teacher->name)->assertSee($car->name)
            ->assertSee($car->registration)->assertSee('Show instructors and vehicles');
    }

    public function test_request_page_preselects_school_from_query(): void
    {
        [$teacher, $school] = $this->offering();
        [, $other] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $response = $this->actingAs($learner)->get(route('learn.private-lessons', ['school' => $school->id]))->assertOk();
        $response->assertSee('<option value="' . $school->id . '" selected', false);
        $response->assertDontSee('<option value="' . $other->id . '" selected', false);
    }

    public function test_learner_can_create_request_with_string_form_values(): void
    {
        [$teacher, $school, $car] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $payload = $this->payload([
            'driving_school_id' => (string) $school->id, 'instructor_id' => (string) $teacher->id,
            'lesson_vehicle_id' => (string) $car->id,
        ]);
        // A real browser submits every form value as a string.
        $this->actingAs($learner)->post(route('learn.private-lessons.store'), $payload)
            ->assertRedirect(route('learn.private-lessons'))->assertSessionHas('success');
        $this->assertDatabaseHas('private_lesson_requests', [
            'learner_id' => $learner->id, 'driving_school_id' => $school->id, 'instructor_id' => $teacher->id,
            'lesson_vehicle_id' => $car->id, 'status' => 'Pending', 'duration_minutes' => 60,
            'meeting_point' => 'School reception',
        ]);
        $this->assertDatabaseHas('school_notifications', ['user_id' => $teacher->id]);
    }

    public function test_past_time_is_rejected(): void
    {
        [$teacher, $school, $car] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $payload = $this->payload([
            'driving_school_id' => (string) $school->id, 'instructor_id' => (string) $teacher->id,
            'lesson_vehicle_id' => (string) $car->id,
            'starts_at' => now()->subDay()->setTime(9, 0)->format('Y-m-d\\TH:i'),
        ]);
        $this->actingAs($learner)->post(route('learn.private-lessons.store'), $payload)->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('private_lesson_requests', 0);
    }

    public function test_another_instructors_vehicle_is_rejected(): void
    {
        [$teacher, $school, $car] = $this->offering();
        [, , $otherCar] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $payload = $this->payload([
            'driving_school_id' => (string) $school->id, 'instructor_id' => (string) $teacher->id,
            'lesson_vehicle_id' => (string) $otherCar->id,
        ]);
        $this->actingAs($learner)->post(route('learn.private-lessons.store'), $payload)->assertSessionHasErrors('lesson_vehicle_id');
        $this->assertDatabaseCount('private_lesson_requests', 0);
    }

    public function test_instructor_not_at_the_school_is_rejected(): void
    {
        [, $school, $car] = $this->offering();
        $outsider = User::factory()->create(['is_active' => true]);
        $learner = User::factory()->create(['is_active' => true]);
        $payload = $this->payload([
            'driving_school_id' => (string) $school->id, 'instructor_id' => (string) $outsider->id,
            'lesson_vehicle_id' => (string) $car->id,
        ]);
        $this->actingAs($learner)->post(route('learn.private-lessons.store'), $payload)->assertSessionHasErrors('instructor_id');
        $this->assertDatabaseCount('private_lesson_requests', 0);
    }

    public function test_instructor_sees_request_and_accept_creates_lesson_and_booking(): void
    {
        $offering = $this->offering();
        [$teacher, $school, $car] = $offering;
        $learner = User::factory()->create(['is_active' => true]);
        $request = $this->requestFor($offering, $learner);
        $this->actingAs($teacher)->get(route('learn.instructor.lessons'))->assertOk()
            ->assertSee('Private lesson requests')->assertSee($learner->name)->assertSee('School reception');
        $this->actingAs($teacher)->post(route('learn.instructor.private-lesson-requests.accept', $request), ['price_npr' => '1500'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Accepted', $request->fresh()->status);
        $lesson = DrivingLesson::firstOrFail();
        $this->assertSame($car->id, $lesson->lesson_vehicle_id);
        $this->assertSame(1500, $lesson->price_npr);
        $this->assertSame($lesson->id, $request->fresh()->driving_lesson_id);
        $this->assertDatabaseHas('lesson_bookings', [
            'driving_lesson_id' => $lesson->id, 'learner_id' => $learner->id, 'status' => 'Booked',
        ]);
        // A second accept is refused: the request is no longer pending.
        $this->actingAs($teacher)->post(route('learn.instructor.private-lesson-requests.accept', $request), ['price_npr' => '1500'])
            ->assertSessionHasErrors('request');
    }

    public function test_decline_marks_request_declined_with_reason(): void
    {
        $offering = $this->offering();
        [$teacher] = $offering;
        $learner = User::factory()->create(['is_active' => true]);
        $request = $this->requestFor($offering, $learner);
        $this->actingAs($teacher)->post(route('learn.instructor.private-lesson-requests.decline', $request), ['decline_reason' => 'Away that week.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Declined', $request->fresh()->status);
        $this->assertSame('Away that week.', $request->fresh()->decline_reason);
        $this->assertDatabaseCount('driving_lessons', 0);
    }

    public function test_non_addressed_instructor_cannot_respond(): void
    {
        $offering = $this->offering();
        [$other] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $request = $this->requestFor($offering, $learner);
        $this->actingAs($other)->post(route('learn.instructor.private-lesson-requests.accept', $request), ['price_npr' => '1500'])->assertForbidden();
        $this->actingAs($other)->post(route('learn.instructor.private-lesson-requests.decline', $request))->assertForbidden();
        $this->assertSame('Pending', $request->fresh()->status);
    }

    public function test_learner_sees_only_their_own_requests(): void
    {
        $offering = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $other = User::factory()->create(['is_active' => true]);
        $this->requestFor($offering, $learner, ['meeting_point' => 'My gate']);
        $this->requestFor($offering, $other, ['meeting_point' => 'Other gate']);
        $this->actingAs($learner)->get(route('learn.private-lessons'))->assertOk()
            ->assertSee('My gate')->assertDontSee('Other gate');
        $this->actingAs($other)->get(route('learn.private-lessons'))->assertOk()
            ->assertSee('Other gate')->assertDontSee('My gate');
    }

    public function test_learner_can_cancel_a_pending_request(): void
    {
        $offering = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $request = $this->requestFor($offering, $learner);
        $this->actingAs($learner)->post(route('learn.private-lessons.cancel', $request))->assertSessionHasNoErrors();
        $this->assertSame('Cancelled', $request->fresh()->status);
    }

    public function test_find_schools_has_no_top_level_button_and_dialog_links_carry_the_school(): void
    {
        [$teacher, $school] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $response = $this->actingAs($learner)->get(route('learn.schools'))->assertOk();
        // No top-level private-lesson button: the only private-lesson links
        // are inside the school dialogs, pre-filtered to that school.
        $response->assertDontSee('href="' . route('learn.private-lessons') . '"', false);
        $response->assertSee('href="' . route('learn.private-lessons', ['school' => $school->id]) . '"', false);
        // The Upcoming classes page itself is untouched: still lists classes.
        $this->get(route('learn.lessons'))->assertOk()->assertSee('Upcoming classes');
    }

    public function test_owner_sees_school_requests_read_only(): void
    {
        $offering = $this->offering();
        [$teacher, $school] = $offering;
        $owner = User::factory()->create(['is_active' => true]);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $owner->id, 'role' => 'Owner']);
        $learner = User::factory()->create(['is_active' => true]);
        $this->requestFor($offering, $learner);
        $this->actingAs($owner)->get(route('learn.school.partner', $school))->assertOk()
            ->assertSee('Private lesson requests')->assertSee($learner->name)->assertSee($teacher->name);
    }
}
