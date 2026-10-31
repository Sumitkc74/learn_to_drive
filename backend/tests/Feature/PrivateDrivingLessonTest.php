<?php
namespace Tests\Feature;

use App\Models\{DrivingLesson, DrivingSchool, LessonBooking, LessonVehicle, SchoolMembership, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivateDrivingLessonTest extends TestCase
{
    use RefreshDatabase;

    private function offering(): array
    {
        $teacher = User::factory()->create(['is_active' => true]);
        $school = DrivingSchool::create(['name' => 'Lesson Academy', 'status' => 'Approved']);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $teacher->id, 'role' => 'Instructor']);
        $car = LessonVehicle::create(['driving_school_id' => $school->id, 'instructor_id' => $teacher->id,
            'name' => 'Training car', 'registration' => 'PRIVATE-'.$teacher->id, 'transmission' => 'Manual']);
        $slot = DrivingLesson::create(['lesson_vehicle_id' => $car->id, 'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(), 'price_npr' => 1500, 'meeting_point' => 'School reception']);
        return [$teacher, $school, $car, $slot];
    }

    public function test_public_search_hides_private_data_and_unapproved_or_removed_instructors(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        $this->get(route('learn.lessons'))->assertOk()->assertSee('Training car')->assertSee('1,500')
            ->assertDontSee($car->registration)->assertDontSee($teacher->email);
        $this->get(route('learn.lessons', ['transmission' => 'Automatic']))->assertOk()->assertDontSee('Training car');
        $school->update(['status' => 'Suspended']);
        $this->get(route('learn.lessons'))->assertOk()->assertDontSee('Training car');
        $school->update(['status' => 'Approved']);
        $teacher->schoolMemberships()->delete();
        $this->get(route('learn.lessons'))->assertOk()->assertDontSee('Training car');
    }

    public function test_booking_is_single_occupancy_and_cancellation_preserves_history_and_reopens_slot(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $other = User::factory()->create(['is_active' => true]);
        $this->actingAs($learner)->post(route('learn.lessons.book', $slot))->assertRedirect(route('learn.lessons.bookings'));
        $booking = LessonBooking::firstOrFail();
        $this->get(route('learn.lessons.bookings'))->assertOk()->assertSee('Training car')->assertSee('Booked');
        $this->actingAs($other)->post(route('learn.lessons.book', $slot))->assertSessionHasErrors('lesson');
        $this->get(route('learn.lessons.bookings'))->assertOk()->assertDontSee('Training car');
        $this->delete(route('learn.lessons.cancel', $booking))->assertNotFound();
        $this->actingAs($learner)->delete(route('learn.lessons.cancel', $booking))->assertSessionHasNoErrors();
        $this->assertSame('Cancelled', $booking->fresh()->status);
        $this->assertNull($booking->fresh()->active_lesson_id);
        $this->actingAs($other)->post(route('learn.lessons.book', $slot))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('lesson_bookings', 2);
    }

    public function test_learner_cannot_book_overlapping_lessons_or_unavailable_provider(): void
    {
        [, , , $first] = $this->offering();
        [, $school, , $second] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $this->actingAs($learner)->post(route('learn.lessons.book', $first))->assertSessionHasNoErrors();
        $this->post(route('learn.lessons.book', $second))->assertSessionHasErrors('lesson');
        $school->update(['status' => 'Suspended']);
        $this->post(route('learn.lessons.book', $second))->assertNotFound();
    }

    public function test_instructor_scheduling_checks_vehicle_ownership_overlaps_and_timezone(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        [, , $foreignCar] = $this->offering();
        $start = now()->addDays(3)->setTime(9, 0)->format('Y-m-d\TH:i');
        $payload = ['lesson_vehicle_id' => $car->id, 'starts_at' => $start, 'duration' => 60, 'price_npr' => 1200, 'meeting_point' => 'Main gate'];
        $this->actingAs($teacher)->get(route('learn.instructor.lessons'))->assertOk()->assertSee($car->registration);
        $this->post(route('learn.instructor.lessons.store'), array_replace($payload, ['lesson_vehicle_id' => $foreignCar->id]))->assertNotFound();
        $this->post(route('learn.instructor.lessons.store'), $payload)->assertSessionHasNoErrors();
        $created = DrivingLesson::latest('id')->first();
        $this->assertSame('03:15', $created->starts_at->format('H:i'));
        $this->post(route('learn.instructor.lessons.store'), $payload)->assertSessionHasErrors('starts_at');
        $this->post(route('learn.instructor.lessons.store'), array_replace($payload, ['duration' => 0]))->assertSessionHasErrors('duration');
    }

    public function test_teacher_can_cancel_own_booked_lesson_but_not_another_teachers(): void
    {
        [$teacher, , , $slot] = $this->offering();
        [, , , $foreign] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $this->actingAs($learner)->post(route('learn.lessons.book', $slot))->assertSessionHasNoErrors();
        $this->actingAs($teacher)->delete(route('learn.instructor.lessons.cancel', $foreign))->assertNotFound();
        $this->delete(route('learn.instructor.lessons.cancel', $slot))->assertSessionHasNoErrors();
        $this->assertTrue($slot->fresh()->cancelled);
        $this->assertSame('Cancelled', LessonBooking::first()->status);
        $this->actingAs($learner)->post(route('learn.lessons.book', $slot))->assertSessionHasErrors('lesson');
    }

    public function test_only_assigned_instructor_can_add_car_and_registration_is_unique(): void
    {
        [$teacher, $school, $car] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $payload = ['driving_school_id' => $school->id, 'name' => 'City car', 'registration' => ' NEW 123 ', 'transmission' => 'Automatic'];
        $this->actingAs($learner)->get(route('learn.instructor.lessons'))->assertForbidden();
        $this->post(route('learn.instructor.vehicles'), $payload)->assertForbidden();
        $this->actingAs($teacher)->post(route('learn.instructor.vehicles'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lesson_vehicles', ['registration' => 'NEW123', 'instructor_id' => $teacher->id]);
        $this->post(route('learn.instructor.vehicles'), $payload)->assertSessionHasErrors('registration');
    }

    public function test_school_card_links_to_only_that_schools_available_lessons(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        [$otherTeacher, $otherSchool, $otherCar] = $this->offering();
        $car->update(['name' => 'Selected school car']);
        $otherCar->update(['name' => 'Other school car']);
        $this->get(route('learn.schools'))->assertOk()
            ->assertSee(route('learn.lessons', ['school' => $school->id]), false);
        $this->get(route('learn.lessons', ['school' => $school->id]))->assertOk()
            ->assertSee('Selected school car')->assertDontSee('Other school car')
            ->assertSee('name="school" value="'.$school->id.'"', false);
        $school->update(['status' => 'Suspended']);
        $this->get(route('learn.lessons', ['school' => $school->id]))->assertNotFound();
    }
}
