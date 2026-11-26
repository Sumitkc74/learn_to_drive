<?php
namespace Tests\Feature;

use App\Models\{DrivingLesson, DrivingSchool, LessonBooking, LessonVehicle, SchoolMembership, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonSelfBookingTest extends TestCase
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

    public function test_instructor_booking_own_lesson_gets_a_friendly_message_not_a_forbidden_page(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        $this->actingAs($teacher)->post(route('learn.lessons.book', $slot))
            ->assertSessionHasErrors(['lesson' => "You can't book a lesson you're teaching."]);
        $this->assertDatabaseCount('lesson_bookings', 0);
    }

    public function test_instructor_joining_own_waitlist_gets_a_friendly_message(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $this->actingAs($learner)->post(route('learn.lessons.book', $slot))->assertSessionHasNoErrors();
        $this->actingAs($teacher)->post(route('learn.lessons.waitlist', $slot), ['action' => 'join'])
            ->assertSessionHasErrors(['lesson' => "You can't join the waitlist for a lesson you're teaching."]);
        $this->assertDatabaseCount('lesson_waitlist', 0);
    }

    public function test_instructor_rescheduling_to_own_lesson_gets_a_friendly_message(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        // A second lesson at the same school and price, taught by the
        // instructor, that the instructor tries to reschedule onto.
        $own = DrivingLesson::create(['lesson_vehicle_id' => $car->id, 'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addHour(), 'price_npr' => 1500, 'meeting_point' => 'School reception']);
        $other = User::factory()->create(['is_active' => true]);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $other->id, 'role' => 'Instructor']);
        $otherCar = LessonVehicle::create(['driving_school_id' => $school->id, 'instructor_id' => $other->id,
            'name' => 'Second car', 'registration' => 'PRIVATE-'.$other->id, 'transmission' => 'Manual']);
        $others = DrivingLesson::create(['lesson_vehicle_id' => $otherCar->id, 'starts_at' => now()->addDays(4),
            'ends_at' => now()->addDays(4)->addHour(), 'price_npr' => 1500, 'meeting_point' => 'School reception']);
        // The instructor books the other instructor's lesson as a learner…
        $this->actingAs($teacher)->post(route('learn.lessons.book', $others))->assertSessionHasNoErrors();
        $booking = LessonBooking::firstOrFail();
        // …then tries to move it onto a lesson they teach themselves.
        $this->actingAs($teacher)->post(route('learn.lessons.reschedule.save', $booking), ['lesson_id' => $own->id])
            ->assertSessionHasErrors(['lesson_id' => "You can't reschedule to a lesson you're teaching."]);
        $this->assertSame($others->id, $booking->fresh()->driving_lesson_id);
    }

    public function test_lesson_card_hides_booking_for_the_instructor_but_shows_it_for_another_learner(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        $learner = User::factory()->create(['is_active' => true]);
        $this->actingAs($teacher)->get(route('learn.lessons'))->assertOk()
            ->assertSee("You're teaching this course.")
            ->assertDontSee('Book this course')
            ->assertDontSee('Confirm course booking');
        $this->actingAs($learner)->get(route('learn.lessons'))->assertOk()
            ->assertSee('Book this course')
            ->assertDontSee("You're teaching this course.");
    }
}
