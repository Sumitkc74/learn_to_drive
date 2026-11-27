<?php

namespace Tests\Feature;

use App\Models\{DrivingLesson, DrivingSchool, LessonVehicle, SchoolMembership, User};
use App\Support\SchoolMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SchoolMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_map_only_lists_approved_schools_and_public_details(): void
    {
        $mapped = DrivingSchool::create(['name' => 'Mapped Academy', 'status' => 'Approved', 'address' => 'Kathmandu', 'phone' => '9800000002', 'latitude' => 27.7, 'longitude' => 85.3, 'email' => 'private@example.com']);
        DrivingSchool::create(['name' => 'Unmapped Academy', 'status' => 'Approved']);
        DrivingSchool::create(['name' => 'Pending Academy', 'status' => 'Pending', 'latitude' => 27, 'longitude' => 85]);
        DrivingSchool::create(['name' => 'Suspended Academy', 'status' => 'Suspended']);
        $content = $this->get(route('learn.schools'))->assertOk()->getContent();
        // Everything before the first <dialog> is the row-card list.
        [$list, $dialogs] = array_pad(explode('<dialog', $content, 2), 2, '');
        $this->assertStringContainsString('Mapped Academy', $list);
        $this->assertStringContainsString('Unmapped Academy', $list);
        $this->assertStringContainsString('data-lat="27.7"', $content);
        // Row cards show the name, area and phone — email stays dialog-only.
        $this->assertStringContainsString('Kathmandu', $list);
        $this->assertStringContainsString('9800000002', $list);
        $this->assertStringNotContainsString('private@example.com', $list);
        $this->assertStringNotContainsString('Location not added yet', $list);
        $this->assertStringNotContainsString('Pending Academy', $content);
        $this->assertStringNotContainsString('Suspended Academy', $content);
        // Each card links to its dialog and to its filtered classes page.
        $this->assertStringContainsString('View details', $list);
        $this->assertStringContainsString('data-open-school-dialog="school-'.$mapped->id.'"', $list);
        $this->assertStringContainsString(route('learn.lessons', ['school' => $mapped->id]), $list);
        // The details live inside the dialog, not in the list.
        $this->assertStringContainsString('Kathmandu', $dialogs);
        $this->assertStringContainsString('private@example.com', $dialogs);
        $this->assertStringContainsString('Location not added yet', $dialogs);
        $this->get(route('learn.schools', ['q' => 'Kathmandu']))->assertOk()->assertSee('Mapped Academy')->assertDontSee('Unmapped Academy');
    }

    public function test_find_schools_renders_row_cards_with_logo_area_phone_and_actions(): void
    {
        $school = DrivingSchool::create(['name' => 'Row Card Academy', 'status' => 'Approved',
            'address' => 'Pokhara', 'phone' => '9800000003', 'email' => 'row@example.com',
            'latitude' => 28.2, 'longitude' => 83.9]);
        $school->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection(SchoolMedia::LOGO_COLLECTION);
        $content = $this->get(route('learn.schools'))->assertOk()->getContent();
        [$list, $dialogs] = array_pad(explode('<dialog', $content, 2), 2, '');
        // One full-width row card: logo left, name + area + phone middle,
        // actions right. Email stays dialog-only.
        $this->assertStringContainsString('logo.png', $list);
        $this->assertStringContainsString('Row Card Academy', $list);
        $this->assertStringContainsString('Pokhara', $list);
        $this->assertStringContainsString('9800000003', $list);
        $this->assertStringNotContainsString('row@example.com', $list);
        $this->assertStringContainsString('View details', $list);
        $this->assertStringContainsString('data-open-school-dialog="school-'.$school->id.'"', $list);
        $this->assertStringContainsString('View available courses', $list);
        $this->assertStringContainsString(route('learn.lessons', ['school' => $school->id]), $list);
        // Map hooks ride on the card itself.
        $this->assertStringContainsString('data-school-marker', $list);
        $this->assertStringContainsString('data-school-id="'.$school->id.'"', $list);
        $this->assertStringContainsString('data-lat="28.2"', $list);
        $this->assertStringContainsString('data-lng="83.9"', $list);
    }

    public function test_school_dialog_contains_logo_gallery_and_contact_but_no_lessons(): void
    {
        $teacher = User::factory()->create(['is_active' => true, 'name' => 'Dialog Instructor']);
        $school = DrivingSchool::create(['name' => 'Dialog Academy', 'status' => 'Approved',
            'address' => 'Lakeside', 'phone' => '9800000001', 'email' => 'dialog@example.com']);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $teacher->id, 'role' => 'Instructor']);
        $car = LessonVehicle::create(['driving_school_id' => $school->id, 'instructor_id' => $teacher->id,
            'name' => 'Dialog car', 'registration' => 'DIALOG-1', 'transmission' => 'Manual']);
        $slot = DrivingLesson::create(['lesson_vehicle_id' => $car->id, 'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(), 'price_npr' => 1500, 'meeting_point' => 'School gate']);
        $school->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection(SchoolMedia::LOGO_COLLECTION);
        $school->addMedia(UploadedFile::fake()->image('photo.png'))->toMediaCollection(SchoolMedia::GALLERY_COLLECTION);

        $learner = User::factory()->create(['is_active' => true]);
        $content = $this->actingAs($learner)->get(route('learn.schools'))->assertOk()->getContent();
        [$list, $dialogs] = array_pad(explode('<dialog', $content, 2), 2, '');
        // The row card below the map shows the name, area and phone.
        $this->assertStringContainsString('Dialog Academy', $list);
        $this->assertStringContainsString('Lakeside', $list);
        $this->assertStringContainsString('9800000001', $list);
        $this->assertStringNotContainsString('dialog@example.com', $list);
        $this->assertStringNotContainsString('Dialog car', $list);
        // The dialog holds the logo, contact details and gallery — but no
        // lessons: the lessons page is the one place that lists them.
        $this->assertStringContainsString('logo.png', $dialogs);
        $this->assertStringContainsString('photo.png', $dialogs);
        $this->assertStringContainsString('Lakeside', $dialogs);
        $this->assertStringContainsString('9800000001', $dialogs);
        $this->assertStringContainsString('dialog@example.com', $dialogs);
        $this->assertStringNotContainsString('Dialog Instructor', $dialogs);
        $this->assertStringNotContainsString('Dialog car', $dialogs);
        $this->assertStringNotContainsString('DIALOG-1', $dialogs);
        $this->assertStringNotContainsString('1,500', $dialogs);
        $this->assertStringNotContainsString('School gate', $dialogs);
        $this->assertStringNotContainsString(route('learn.lessons.book', $slot), $dialogs);
    }

    public function test_school_dialog_shows_no_lesson_or_sign_in_prompt_to_guests(): void
    {
        $teacher = User::factory()->create(['is_active' => true]);
        $school = DrivingSchool::create(['name' => 'Guest Academy', 'status' => 'Approved']);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $teacher->id, 'role' => 'Instructor']);
        $car = LessonVehicle::create(['driving_school_id' => $school->id, 'instructor_id' => $teacher->id,
            'name' => 'Guest car', 'registration' => 'GUEST-1', 'transmission' => 'Manual']);
        $slot = DrivingLesson::create(['lesson_vehicle_id' => $car->id, 'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(), 'price_npr' => 1500, 'meeting_point' => 'School gate']);
        $dialogs = array_pad(explode('<dialog', $this->get(route('learn.schools'))->assertOk()->getContent(), 2), 2, '')[1];
        // Guests see the link to the classes page instead of the lesson
        // itself; booking (and its sign-in prompt) lives on that page.
        $this->assertStringContainsString('View available courses', $dialogs);
        $this->assertStringNotContainsString('Guest car', $dialogs);
        $this->assertStringNotContainsString('Sign in to book', $dialogs);
        $this->assertStringNotContainsString(route('learn.lessons.book', $slot), $dialogs);
    }

    public function test_school_names_are_escaped_and_empty_search_is_supported(): void
    {
        DrivingSchool::create(['name' => '<script>alert(1)</script>', 'status' => 'Approved', 'latitude' => 0, 'longitude' => 0]);
        $this->get(route('learn.schools'))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertSee('data-lat="0"', false);
        $this->get(route('learn.schools', ['q' => 'No match']))->assertOk()->assertSee('No approved schools match');
    }

    public function test_admin_coordinates_require_a_valid_pair_and_can_be_cleared(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);
        $school = DrivingSchool::create(['name' => 'Academy']);
        $url = route('admin.schools.update', $school);
        $base = ['name' => 'Academy', 'status' => 'Pending'];
        $this->actingAs($admin)->patch($url, $base + ['latitude' => 27])->assertSessionHasErrors('longitude');
        $this->patch($url, $base + ['latitude' => 91, 'longitude' => 181])->assertSessionHasErrors(['latitude', 'longitude']);
        $this->patch($url, $base + ['latitude' => 27.7, 'longitude' => 85.3])->assertSessionHasNoErrors();
        $this->assertEquals(27.7, $school->fresh()->latitude);
        $this->patch($url, $base + ['latitude' => '', 'longitude' => ''])->assertSessionHasNoErrors();
        $this->assertNull($school->fresh()->latitude);
        $this->assertNull($school->fresh()->longitude);
    }
}
