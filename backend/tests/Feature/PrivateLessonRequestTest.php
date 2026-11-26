<?php
namespace Tests\Feature;

use App\Models\{DrivingLesson, DrivingSchool, LessonBooking, LessonVehicle, PrivateLessonRequest, SchoolMembership, User};
use Carbon\Carbon;
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

    public function test_guest_can_browse_the_page_but_cannot_submit(): void
    {
        [$teacher, $school, $car] = $this->offering();
        $this->get(route('learn.private-lessons', ['school' => $school->id, 'instructor' => $teacher->id]))->assertOk()
            // Guests see the school, instructor, car and usual times…
            // …but not the plate: the guest-visible chips show the car
            // type only, so a test-looking registration never reads as
            // fake data to visitors.
            ->assertSee($teacher->name)->assertSee($car->name)->assertDontSee($car->registration)
            ->assertSee('9:00 AM')
            // …and the sign-in action instead of the request form.
            ->assertSee('Sign in to request this session')
            ->assertDontSee('Request this session')
            ->assertDontSee('name="starts_at"', false);
        $this->post(route('learn.private-lessons.store'), $this->payload())->assertRedirect(route('learn.login'));
    }

    public function test_guest_sign_in_returns_to_private_lessons_with_selection(): void
    {
        [$teacher, $school] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $target = route('learn.private-lessons', ['school' => $school->id, 'instructor' => $teacher->id]);
        $this->get(route('learn.login', ['redirect' => $target]))->assertOk();
        $this->post(route('learn.login'), ['email' => $learner->email, 'password' => 'password'])->assertRedirect($target);
        // And back on the page the selection is still there.
        $this->get($target)->assertOk()->assertSee('<option value="' . $school->id . '" selected', false);
    }

    public function test_sign_in_ignores_a_redirect_off_the_private_lessons_page(): void
    {
        $learner = User::factory()->create(['is_active' => true]);
        $this->get(route('learn.login', ['redirect' => 'https://example.com/evil']))->assertOk();
        $this->post(route('learn.login'), ['email' => $learner->email, 'password' => 'password'])->assertRedirect(route('learn.account'));
    }

    public function test_learner_sees_picker_with_instructors_and_vehicles(): void
    {
        [$teacher, $school, $car] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $this->actingAs($learner)->get(route('learn.private-lessons', ['school' => $school->id, 'instructor' => $teacher->id]))->assertOk()
            ->assertSee($teacher->name)->assertSee($car->name)
            ->assertDontSee($car->registration)->assertSee('Show instructors and vehicles');
    }

    public function test_picker_hides_car_registration_but_own_request_keeps_it(): void
    {
        [$teacher, $school, $car] = $this->offering();
        // Mirror the live test-data plate.
        $car->update(['registration' => 'TEST0002']);
        // The guest-visible picker shows the car type but never the plate —
        // neither on the vehicle chips nor in the "Your request" summary,
        // both of which a guest can see.
        $this->get(route('learn.private-lessons', ['school' => $school->id, 'instructor' => $teacher->id]))->assertOk()
            ->assertSee('Training car · Manual')
            ->assertDontSee('TEST0002');
        // The learner's own request is operational, not public: the plate
        // stays visible in their own requests list so they can identify
        // their car.
        $learner = User::factory()->create(['is_active' => true]);
        $this->requestFor([$teacher, $school, $car], $learner);
        $this->actingAs($learner)->get(route('learn.private-lessons', ['school' => $school->id, 'instructor' => $teacher->id]))->assertOk()
            ->assertSee('TEST0002');
    }

    public function test_time_chips_come_from_the_instructors_usual_times(): void
    {
        [$teacher, $school, $car] = $this->offering();
        $slot = DrivingLesson::create(['lesson_vehicle_id' => $car->id,
            'starts_at' => now()->addDays(2)->setTime(9, 0), 'ends_at' => now()->addDays(2)->setTime(10, 0),
            'price_npr' => 1500, 'meeting_point' => 'School gate']);
        $label = $slot->starts_at->copy()->timezone('Asia/Kathmandu')->format('g:i A');
        $this->get(route('learn.private-lessons', ['school' => $school->id, 'instructor' => $teacher->id]))->assertOk()
            ->assertSee('Time')->assertSee($label);
    }

    public function test_picking_chips_submits_the_same_values_as_the_old_form(): void
    {
        [$teacher, $school, $car] = $this->offering();
        $slot = DrivingLesson::create(['lesson_vehicle_id' => $car->id,
            'starts_at' => now()->addDays(3)->setTime(9, 0), 'ends_at' => now()->addDays(3)->setTime(10, 0),
            'price_npr' => 1500, 'meeting_point' => 'School gate']);
        $learner = User::factory()->create(['is_active' => true]);
        $day = now()->addDays(4)->timezone('Asia/Kathmandu')->toDateString();
        $time = '11:00';
        $params = ['school' => $school->id, 'instructor' => $teacher->id, 'vehicle' => $car->id,
            'day' => $day, 'time' => $time, 'duration' => 90, 'meeting_point' => 'School gate'];
        $this->actingAs($learner)->get(route('learn.private-lessons', $params))->assertOk()
            ->assertSee('Your request')
            ->assertSee('name="starts_at" value="' . $day . 'T' . $time . '"', false)
            ->assertSee('name="duration" value="90"', false)
            ->assertSee('name="meeting_point" value="School gate"', false)
            ->assertSee('Request this session');
        $this->actingAs($learner)->post(route('learn.private-lessons.store'), [
            'driving_school_id' => (string) $school->id, 'instructor_id' => (string) $teacher->id,
            'lesson_vehicle_id' => (string) $car->id, 'starts_at' => $day . 'T' . $time,
            'duration' => '90', 'meeting_point' => 'School gate',
        ])->assertRedirect(route('learn.private-lessons'))->assertSessionHas('success');
        $this->assertDatabaseHas('private_lesson_requests', [
            'learner_id' => $learner->id, 'driving_school_id' => $school->id, 'instructor_id' => $teacher->id,
            'lesson_vehicle_id' => $car->id, 'status' => 'Pending', 'duration_minutes' => 90,
            'meeting_point' => 'School gate',
        ]);
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
            ->assertSee('Private session requests')->assertSee($learner->name)->assertSee('School reception');
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
            ->assertSee('Private session requests')->assertSee($learner->name)->assertSee($teacher->name);
    }

    // The test database stores a lesson's wall time as UTC, so a lesson
    // meant to run at a Nepal wall time is built from that wall time
    // first and then converted to UTC.
    private function lessonAt(array $offering, string $day, string $from, string $to): DrivingLesson
    {
        [, , $car] = $offering;
        return DrivingLesson::create(['lesson_vehicle_id' => $car->id,
            'starts_at' => Carbon::parse($day.' '.$from, 'Asia/Kathmandu')->setTimezone('UTC'),
            'ends_at' => Carbon::parse($day.' '.$to, 'Asia/Kathmandu')->setTimezone('UTC'),
            'price_npr' => 1500, 'meeting_point' => 'School gate']);
    }

    public function test_busy_time_does_not_appear_as_a_chip_but_a_free_time_does(): void
    {
        $offering = $this->offering();
        [$teacher, $school] = $offering;
        $day = now()->timezone('Asia/Kathmandu')->addDays(2)->toDateString();
        $otherDay = now()->timezone('Asia/Kathmandu')->addDays(3)->toDateString();
        // A 09:00 lesson on another day gives the 09:00 chip; it is free on $day.
        $this->lessonAt($offering, $otherDay, '09:00', '10:00');
        // A 16:00 lesson on $day gives the 16:00 chip, but the instructor
        // is teaching then, so it must not be offered for $day.
        $this->lessonAt($offering, $day, '16:00', '17:00');
        $this->get(route('learn.private-lessons', ['school' => $school->id, 'instructor' => $teacher->id, 'day' => $day]))->assertOk()
            ->assertSee('time=09%3A00', false)
            ->assertDontSee('time=16%3A00', false);
    }

    public function test_fallback_times_are_offered_when_every_derived_time_is_busy(): void
    {
        $offering = $this->offering();
        [$teacher, $school] = $offering;
        $day = now()->timezone('Asia/Kathmandu')->addDays(2)->toDateString();
        $this->lessonAt($offering, $day, '16:00', '17:00');
        // The only derived time is busy, so the usual daytime slots take
        // over — filtered the same way, so 16:00 stays out.
        $this->get(route('learn.private-lessons', ['school' => $school->id, 'instructor' => $teacher->id, 'day' => $day]))->assertOk()
            ->assertDontSee('time=16%3A00', false)
            ->assertSee('time=09%3A00', false);
    }

    public function test_busy_selected_time_is_cleared_and_submitting_at_a_busy_time_is_still_rejected(): void
    {
        $offering = $this->offering();
        [$teacher, $school, $car] = $offering;
        $day = now()->timezone('Asia/Kathmandu')->addDays(2)->toDateString();
        $this->lessonAt($offering, $day, '16:00', '17:00');
        $learner = User::factory()->create(['is_active' => true]);
        // A time picked before the day was chosen is cleared once the day
        // makes it busy, so the form cannot offer a slot store() rejects.
        $this->actingAs($learner)->get(route('learn.private-lessons', ['school' => $school->id, 'instructor' => $teacher->id,
                'vehicle' => $car->id, 'day' => $day, 'time' => '16:00', 'duration' => 60, 'meeting_point' => 'School gate']))->assertOk()
            ->assertDontSee('name="starts_at"', false)
            ->assertSee('Pick a day and a time above to request.');
        // And the store() backstop still rejects the busy slot directly.
        $this->actingAs($learner)->post(route('learn.private-lessons.store'), $this->payload([
                'driving_school_id' => (string) $school->id, 'instructor_id' => (string) $teacher->id,
                'lesson_vehicle_id' => (string) $car->id, 'starts_at' => $day.'T16:00',
                'duration' => '60', 'meeting_point' => 'School gate',
            ]))->assertSessionHasErrors(['starts_at' => 'This instructor already has a lesson at this time.']);
        $this->assertDatabaseCount('private_lesson_requests', 0);
    }
}
