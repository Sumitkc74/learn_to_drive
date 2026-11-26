<?php
namespace Tests\Feature;

use App\Models\{DrivingLesson, DrivingSchool, LessonVehicle, SchoolMembership, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonBookingReturnTest extends TestCase
{
    use RefreshDatabase;

    private function makeLesson(DrivingSchool $school, User $teacher, string $carName, string $registration, int $dayOffset, int $hour): DrivingLesson
    {
        $car = LessonVehicle::create(['driving_school_id' => $school->id, 'instructor_id' => $teacher->id,
            'name' => $carName, 'registration' => $registration, 'transmission' => 'Automatic']);
        return DrivingLesson::create(['lesson_vehicle_id' => $car->id,
            'starts_at' => now()->addDays($dayOffset)->setTime($hour, 0), 'ends_at' => now()->addDays($dayOffset)->setTime($hour + 1, 0),
            'price_npr' => 1000, 'meeting_point' => $school->name.', Main Road', 'cancelled' => false]);
    }

    private function fixtures(): array
    {
        $teacher = User::factory()->create(['is_active' => true]);
        $school = DrivingSchool::create(['name' => 'Bagmati Driving Center', 'status' => 'Approved']);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $teacher->id, 'role' => 'Instructor']);
        $first = $this->makeLesson($school, $teacher, 'Swift', 'TEST-1', 2, 9);
        $second = $this->makeLesson($school, $teacher, 'Alto', 'TEST-2', 3, 11);
        $learner = User::factory()->create(['is_active' => true]);
        return [$school, $first, $second, $learner];
    }

    public function test_guest_sign_in_link_carries_the_lesson_and_anchor(): void
    {
        [$school, $first, $second, $learner] = $this->fixtures();
        $html = $this->get(route('learn.lessons'))->assertOk()->getContent();
        // The redirect value is URL-encoded in the login link, so the
        // lesson id and the #fragment appear as lesson%3D<n> and %23.
        $this->assertStringContainsString('lesson%3D'.$first->id, $html);
        $this->assertStringContainsString('%23lesson-'.$first->id, $html);
        $this->assertStringContainsString('lesson%3D'.$second->id, $html);
        $this->assertStringContainsString('id="lesson-'.$first->id.'"', $html);
        $this->assertStringContainsString('id="lesson-'.$second->id.'"', $html);
    }

    public function test_signed_in_list_opens_only_the_selected_lesson(): void
    {
        [$school, $first, $second, $learner] = $this->fixtures();
        $html = $this->actingAs($learner)->get(route('learn.lessons', ['lesson' => $first->id]))->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, '<details open>'));
        $this->assertSame(1, substr_count($html, '<details>'));
    }

    public function test_unknown_lesson_id_opens_nothing(): void
    {
        [$school, $first, $second, $learner] = $this->fixtures();
        $html = $this->actingAs($learner)->get(route('learn.lessons', ['lesson' => 999999]))->assertOk()->getContent();
        $this->assertSame(0, substr_count($html, '<details open>'));
        $this->assertSame(2, substr_count($html, '<details>'));
    }

    public function test_school_filter_survives_with_the_lesson_param(): void
    {
        [$school, $first, $second, $learner] = $this->fixtures();
        $otherTeacher = User::factory()->create(['is_active' => true]);
        $otherSchool = DrivingSchool::create(['name' => 'Nepal Driving Center', 'status' => 'Approved']);
        SchoolMembership::create(['driving_school_id' => $otherSchool->id, 'user_id' => $otherTeacher->id, 'role' => 'Instructor']);
        $this->makeLesson($otherSchool, $otherTeacher, 'Corolla', 'TEST-3', 2, 13);

        $html = $this->actingAs($learner)->get(route('learn.lessons', ['school' => $school->id, 'lesson' => $first->id]))->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, '<details open>'));
        $this->assertStringContainsString('Swift', $html);
        $this->assertStringNotContainsString('Corolla', $html);
    }

    public function test_guest_sign_in_returns_with_the_confirmation_open(): void
    {
        [$school, $first, $second, $learner] = $this->fixtures();
        $html = $this->get(route('learn.lessons'))->assertOk()->getContent();
        // The first "Sign in to book" button carries the first lesson's
        // redirect; read it back exactly as the browser would send it.
        $this->assertEquals(1, preg_match('/class="button" href="[^"]*learn\/login\?redirect=([^"]+)"/', $html, $matches));
        // The captured group is the URL-encoded redirect value itself;
        // decode it back to the lessons URL with its lesson id and anchor.
        $redirect = urldecode($matches[1]);
        $this->assertStringContainsString('lesson='.$first->id, $redirect);
        $this->assertStringEndsWith('#lesson-'.$first->id, $redirect);

        $this->get(route('learn.login', ['redirect' => $redirect]))->assertOk()
            ->assertSee('Sign in to book this course.');
        $location = $this->post(route('learn.login'), ['email' => $learner->email, 'password' => 'password'])
            ->headers->get('Location');
        $this->assertStringContainsString('lesson='.$first->id, $location);
        $this->assertStringEndsWith('#lesson-'.$first->id, $location);

        // Fragments never reach the server, so the follow-up list request
        // carries the lesson id without it — and the confirmation is open.
        $this->assertSame(1, substr_count(
            $this->get(route('learn.lessons', ['lesson' => $first->id]))->assertOk()->getContent(), '<details open>'));
    }
}
