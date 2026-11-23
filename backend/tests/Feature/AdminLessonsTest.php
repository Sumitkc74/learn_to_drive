<?php

namespace Tests\Feature;

use App\Models\DrivingLesson;
use App\Models\DrivingSchool;
use App\Models\LessonBooking;
use App\Models\LessonVehicle;
use App\Models\SchoolLessonPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminLessonsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('is_seed_admin', true)->firstOrFail();
    }

    private function setupData(): array
    {
        $learner = User::factory()->create(['is_active' => true, 'name' => 'Lesson Learner']);
        $instructor = User::factory()->create(['is_active' => true, 'name' => 'Lesson Instructor']);
        $school = DrivingSchool::create(['name' => 'Lesson Admin School', 'status' => 'Approved']);
        $school->memberships()->create(['user_id' => $instructor->id, 'role' => 'Instructor']);
        $vehicle = LessonVehicle::create(['driving_school_id' => $school->id, 'instructor_id' => $instructor->id, 'name' => 'Admin Alto', 'registration' => 'ADMIN-1', 'transmission' => 'Manual']);
        $lesson = DrivingLesson::create(['lesson_vehicle_id' => $vehicle->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'price_npr' => 1500, 'meeting_point' => 'Admin School Gate']);
        $booking = LessonBooking::create(['driving_lesson_id' => $lesson->id, 'learner_id' => $learner->id, 'active_lesson_id' => $lesson->id]);

        $otherSchool = DrivingSchool::create(['name' => 'Other Admin School', 'status' => 'Approved']);
        $otherVehicle = LessonVehicle::create(['driving_school_id' => $otherSchool->id, 'instructor_id' => $instructor->id, 'name' => 'Other Swift', 'registration' => 'ADMIN-2', 'transmission' => 'Automatic']);
        $cancelledLesson = DrivingLesson::create(['lesson_vehicle_id' => $otherVehicle->id, 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour(), 'price_npr' => 2000, 'meeting_point' => 'Other Gate', 'cancelled' => true]);
        $cancelledBooking = LessonBooking::create(['driving_lesson_id' => $cancelledLesson->id, 'learner_id' => $learner->id, 'status' => 'Cancelled']);

        return [$learner, $instructor, $school, $vehicle, $lesson, $booking, $otherSchool, $cancelledLesson, $cancelledBooking];
    }

    public function test_admin_can_view_lessons_list_with_lesson_and_booking_data(): void
    {
        $this->setupData();

        $this->actingAs($this->admin())->get(route('admin.lessons.index'))->assertOk()
            ->assertSee('Lesson Admin School')
            ->assertSee('Lesson Instructor')
            ->assertSee('Admin Alto')
            ->assertSee('Admin School Gate')
            ->assertSee('Rs 1,500')
            ->assertSee('Scheduled')
            ->assertSee('Lesson Learner')
            ->assertSee('Other Admin School')
            ->assertSee('Cancelled');
    }

    public function test_lessons_school_and_status_filters_narrow_the_list(): void
    {
        [, , $school, , , , $otherSchool] = $this->setupData();

        $this->actingAs($this->admin())->get(route('admin.lessons.index', ['school' => (string) $school->id]))->assertOk()
            ->assertSee('Admin Alto')
            ->assertDontSee('Other Swift');

        $this->actingAs($this->admin())->get(route('admin.lessons.index', ['school' => (string) $otherSchool->id]))->assertOk()
            ->assertSee('Other Swift')
            ->assertDontSee('Admin Alto');

        $this->actingAs($this->admin())->get(route('admin.lessons.index', ['status' => 'cancelled']))->assertOk()
            ->assertSee('Other Gate')
            ->assertDontSee('Admin School Gate');
    }

    public function test_admin_can_view_bookings_list_with_learner_and_payment_state(): void
    {
        [, , , , , $booking] = $this->setupData();
        $payment = new SchoolLessonPayment(['lesson_booking_id' => $booking->id, 'amount_paisa' => 150000, 'gateway' => 'khalti', 'live' => false, 'status' => 'Paid']);
        $payment->id = (string) Str::uuid();
        $payment->save();

        $this->actingAs($this->admin())->get(route('admin.lesson-bookings.index'))->assertOk()
            ->assertSee('Lesson Learner')
            ->assertSee('Lesson Admin School')
            ->assertSee('Booked')
            ->assertSee('NPR 1,500.00')
            ->assertSee('Paid')
            ->assertSee('Cancelled');
    }

    public function test_bookings_without_a_payment_show_no_payment(): void
    {
        $this->setupData();

        $this->actingAs($this->admin())->get(route('admin.lesson-bookings.index'))->assertOk()
            ->assertSee('No payment');
    }

    public function test_bookings_school_and_status_filters_narrow_the_list(): void
    {
        [, , $school, , , , $otherSchool] = $this->setupData();

        $this->actingAs($this->admin())->get(route('admin.lesson-bookings.index', ['school' => (string) $school->id]))->assertOk()
            ->assertSee('Admin Alto')
            ->assertDontSee('Other Swift');

        $this->actingAs($this->admin())->get(route('admin.lesson-bookings.index', ['status' => 'Cancelled']))->assertOk()
            ->assertSee('Cancelled')
            ->assertSee('Other Swift')
            ->assertDontSee('Admin Alto');
    }

    public function test_non_admin_is_forbidden(): void
    {
        $this->setupData();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.lessons.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.lesson-bookings.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.lessons.index'))->assertRedirect(route('login'));
        $this->get(route('admin.lesson-bookings.index'))->assertRedirect(route('login'));
    }

    public function test_dashboard_cards_link_to_their_lists(): void
    {
        $this->setupData();

        $response = $this->actingAs($this->admin())->get(route('adminDashboard'))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $links = $xpath->query('//a[div[@class="ltd-stat-card"]]');
        $this->assertSame(6, $links->length);

        $hrefs = [];
        foreach ($links as $link) {
            $hrefs[] = $link->getAttribute('href');
        }
        $this->assertSame([
            route('allUser'),
            route('allUser', ['role' => 'PremiumUser']),
            route('allUser', ['joined' => 'this_month']),
            route('admin.schools.index'),
            route('admin.schools.index', ['status' => 'Pending']),
            route('admin.lesson-bookings.index'),
        ], $hrefs);
    }

    public function test_admin_search_finds_lessons_and_bookings_pages(): void
    {
        $this->setupData();

        $this->actingAs($this->admin())->get(route('adminSearch', ['q' => 'lesson']))->assertOk()
            ->assertSee('<a href="'.route('admin.lessons.index').'">Lessons</a>', false)
            ->assertSee('<a href="'.route('admin.lesson-bookings.index').'">Lesson bookings</a>', false);
    }
}
