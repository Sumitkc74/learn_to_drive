<?php
namespace Tests\Feature;

use App\Models\{DrivingLesson, DrivingSchool, LessonBooking, LessonVehicle, SchoolMembership, User};
use App\Support\SchoolMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_public_search_shows_car_registration_but_hides_private_email_and_unapproved_or_removed_instructors(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        // The lesson card is a bookable offer: the car's registration is
        // shown so the learner knows which car they are booking. The
        // instructor's email stays private.
        $this->get(route('learn.lessons'))->assertOk()->assertSee('Training car')->assertSee('1,500')
            ->assertSee($car->registration)->assertDontSee($teacher->email);
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

    public function test_instructor_can_publish_lesson_with_string_form_values(): void
    {
        // A real browser submits every form value as a string. Carbon 3
        // rejects a string duration in addMinutes(), so publishing a lesson
        // 500'd live even though the integer-valued tests passed.
        [$teacher, $school, $car] = $this->offering();
        $start = now()->addDays(3)->setTime(9, 0)->format('Y-m-d\TH:i');
        $payload = ['lesson_vehicle_id' => (string) $car->id, 'starts_at' => $start,
            'duration' => '60', 'price_npr' => '1200', 'meeting_point' => 'Main gate'];
        $this->actingAs($teacher)->post(route('learn.instructor.lessons.store'), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('driving_lessons', 2);
        $this->actingAs($teacher)->get(route('learn.instructor.lessons'))->assertOk()
            ->assertSee('Training car')->assertSee('1,200');
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

    public function test_lesson_card_shows_school_logo_and_car_registration(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        $school->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection(SchoolMedia::LOGO_COLLECTION);
        $this->get(route('learn.lessons'))->assertOk()->assertSee('logo.png')->assertSee($car->registration);
        // Without a logo the card falls back to the school's initial.
        [$otherTeacher, $otherSchool, $otherCar] = $this->offering();
        $otherSchool->update(['name' => 'Zebra Academy']);
        $this->get(route('learn.lessons'))->assertOk()->assertSee('school-logo-fallback-small', false);
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
            ->assertSee('<option value="'.$school->id.'" selected>', false);
        $school->update(['status' => 'Suspended']);
        $this->get(route('learn.lessons', ['school' => $school->id]))->assertNotFound();
    }

    public function test_find_schools_page_has_a_distinct_upcoming_classes_button(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        $this->get(route('learn.schools'))->assertOk()
            ->assertSee('Book a private driving lesson')
            ->assertSee('<a class="button secondary" href="'.route('learn.lessons').'">Upcoming classes</a>', false);
    }

    public function test_upcoming_classes_page_shows_time_car_and_instructor_on_each_card(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        $date = $slot->starts_at->copy()->timezone('Asia/Kathmandu')->format('d M Y');
        $time = $slot->starts_at->copy()->timezone('Asia/Kathmandu')->format('H:i');
        $this->get(route('learn.lessons'))->assertOk()
            ->assertSee('Upcoming classes')
            ->assertSee($date)
            ->assertSee($time)
            ->assertSee('Training car')
            ->assertSee($teacher->name)
            ->assertSee($car->registration)
            ->assertSee('Nepal time');
    }

    public function test_institute_dropdown_lists_approved_schools_and_filters_classes(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        [$otherTeacher, $otherSchool, $otherCar] = $this->offering();
        $otherSchool->update(['name' => 'Zebra Academy']);
        $car->update(['name' => 'Selected school car']);
        $otherCar->update(['name' => 'Other school car']);
        $this->get(route('learn.lessons'))->assertOk()
            ->assertSee('<select name="school">', false)
            ->assertSee('All institutes')
            ->assertSee('Lesson Academy')
            ->assertSee('Zebra Academy');
        $this->get(route('learn.lessons', ['school' => $otherSchool->id]))->assertOk()
            ->assertSee('Other school car')->assertDontSee('Selected school car')
            ->assertSee('<option value="'.$otherSchool->id.'" selected>', false);
    }

    public function test_search_by_car_returns_only_matching_lessons(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        [$otherTeacher, $otherSchool, $otherCar] = $this->offering();
        $car->update(['name' => 'Red Hatchback']);
        $otherCar->update(['name' => 'Blue Sedan']);
        $this->get(route('learn.lessons', ['car' => 'Hatchback']))->assertOk()
            ->assertSee('Red Hatchback')->assertDontSee('Blue Sedan');
    }

    public function test_search_by_instructor_returns_only_matching_lessons(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        [$otherTeacher, $otherSchool, $otherCar] = $this->offering();
        $teacher->update(['name' => 'Asha Sharma']);
        $otherTeacher->update(['name' => 'Bikash Thapa']);
        $this->get(route('learn.lessons', ['instructor' => 'Asha']))->assertOk()
            ->assertSee('Asha Sharma')->assertDontSee('Bikash Thapa');
    }

    public function test_search_by_date_returns_only_that_days_lessons(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        [$otherTeacher, $otherSchool, $otherCar] = $this->offering();
        $car->update(['name' => 'Day car']);
        $otherCar->update(['name' => 'Other day car']);
        // Move the other lesson to a different day so the date filter can tell them apart.
        $otherLesson = DrivingLesson::where('lesson_vehicle_id', $otherCar->id)->firstOrFail();
        $otherLesson->update(['starts_at' => now()->addDays(5), 'ends_at' => now()->addDays(5)->addHour()]);
        $day = $slot->starts_at->copy()->timezone('Asia/Kathmandu')->format('Y-m-d');
        $otherDay = $otherLesson->starts_at->copy()->timezone('Asia/Kathmandu')->format('Y-m-d');
        $this->get(route('learn.lessons', ['date' => $day]))->assertOk()
            ->assertSee('Day car')->assertDontSee('Other day car');
        $this->get(route('learn.lessons', ['date' => $otherDay]))->assertOk()
            ->assertSee('Other day car')->assertDontSee('Day car');
    }
}
