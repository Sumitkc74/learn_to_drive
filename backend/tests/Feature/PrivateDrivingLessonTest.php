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

    public function test_public_search_hides_car_registration_and_private_email_and_unapproved_or_removed_instructors(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        // The lesson card is a bookable offer: the car's name and type are
        // shown, but the registration is hidden on the public listing so a
        // test-looking plate never reads as fake data to visitors. The
        // instructor's email stays private.
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

    public function test_lesson_card_shows_school_logo_but_hides_car_registration(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        $school->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection(SchoolMedia::LOGO_COLLECTION);
        $this->get(route('learn.lessons'))->assertOk()->assertSee('logo.png')->assertDontSee($car->registration);
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

    public function test_find_schools_page_has_no_top_level_upcoming_classes_button_but_dialog_links_to_filtered_classes(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        $this->get(route('learn.schools'))->assertOk()
            ->assertSee('Request a private session')
            // The redundant top-level button is gone: no unfiltered
            // "View available courses" link on the page.
            ->assertDontSee('<a class="button secondary" href="'.route('learn.lessons').'">View available courses</a>', false)
            // Each school's dialog links to its own filtered classes.
            ->assertSee('<a class="button secondary" href="'.route('learn.lessons', ['school' => $school->id]).'">View available courses</a>', false);
    }

    public function test_school_dialog_lists_no_lessons_only_links_to_the_classes_page(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        $this->get(route('learn.schools'))->assertOk()
            // The dialog keeps both action links…
            ->assertSee('Request a private session')
            ->assertSee('<a class="button secondary" href="'.route('learn.lessons', ['school' => $school->id]).'">View available courses</a>', false)
            // …but no longer renders the school's lessons inline: the
            // lessons page is the one place that lists them.
            ->assertDontSee('Upcoming lessons')
            ->assertDontSee('Book this course')
            ->assertDontSee('No upcoming lessons from this school yet.');
    }

    public function test_upcoming_classes_page_shows_time_car_and_instructor_on_each_card(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        $date = $slot->starts_at->copy()->timezone('Asia/Kathmandu')->format('d M Y');
        $time = $slot->starts_at->copy()->timezone('Asia/Kathmandu')->format('H:i');
        $this->get(route('learn.lessons'))->assertOk()
            ->assertSee('Available courses')
            ->assertSee($date)
            ->assertSee($time)
            ->assertSee('Training car')
            ->assertSee('Training car · Manual')
            ->assertSee($teacher->name)
            ->assertDontSee($car->registration)
            ->assertSee('Nepal time')
            // The card is one compact row: values only, no label tags.
            ->assertSee('class="card lesson-row"', false)
            ->assertSee('Rs 1,500')
            ->assertSee('Lesson Academy')
            ->assertSee('School reception')
            ->assertDontSee('<dl')
            ->assertDontSee('<dt>');
    }

    public function test_public_lesson_listing_shows_car_type_but_not_registration_while_own_booking_keeps_it(): void
    {
        [$teacher, $school, $car, $slot] = $this->offering();
        // Mirror the live test-data plate: the public listing must show
        // the car type but never the plate itself.
        $car->update(['registration' => 'TEST0002']);
        $this->get(route('learn.lessons'))->assertOk()
            ->assertSee('Training car · Manual')
            ->assertDontSee('TEST0002');
        // The learner's own booking is operational, not public: the plate
        // stays visible there so they can identify their car.
        $learner = User::factory()->create(['is_active' => true]);
        $this->actingAs($learner)->post(route('learn.lessons.book', $slot))->assertSessionHasNoErrors();
        $this->get(route('learn.lessons.bookings'))->assertOk()->assertSee('TEST0002');
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

    public function test_upcoming_classes_filter_form_is_a_compact_bar(): void
    {
        $this->offering();
        $this->get(route('learn.lessons'))->assertOk()
            ->assertSee('<form method="get" class="filters">', false)
            ->assertSee('name="school"', false)
            ->assertSee('name="date"', false)
            ->assertSee('name="car"', false)
            ->assertSee('name="instructor"', false)
            ->assertSee('name="transmission"', false)
            ->assertSee('name="availability"', false)
            ->assertDontSee('class="card stack"')
            // Other pages keep the " · Learn to Drive" tab suffix.
            ->assertSee('<title>Available courses · Learn to Drive</title>', false);
    }
}
