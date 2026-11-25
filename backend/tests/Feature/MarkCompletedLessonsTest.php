<?php
namespace Tests\Feature;

use App\Models\{DrivingLesson, DrivingSchool, LessonBooking, LessonVehicle, SchoolMembership, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MarkCompletedLessonsTest extends TestCase
{
    use RefreshDatabase;

    private function bookedLesson(\DateTimeInterface $start, \DateTimeInterface $end, string $status = 'Booked', bool $cancelled = false): LessonBooking
    {
        $teacher = User::factory()->create(['is_active' => true]);
        $school = DrivingSchool::create(['name' => 'Lesson Academy', 'status' => 'Approved']);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $teacher->id, 'role' => 'Instructor']);
        $car = LessonVehicle::create(['driving_school_id' => $school->id, 'instructor_id' => $teacher->id,
            'name' => 'Training car', 'registration' => 'COMPLETE-'.$teacher->id, 'transmission' => 'Manual']);
        $slot = DrivingLesson::create(['lesson_vehicle_id' => $car->id, 'starts_at' => $start,
            'ends_at' => $end, 'price_npr' => 1500, 'meeting_point' => 'School reception', 'cancelled' => $cancelled]);
        $learner = User::factory()->create(['is_active' => true]);

        return LessonBooking::create(['driving_lesson_id' => $slot->id, 'learner_id' => $learner->id,
            'active_lesson_id' => $slot->id, 'status' => $status]);
    }

    public function test_past_booked_lesson_becomes_completed_and_releases_the_active_claim(): void
    {
        $booking = $this->bookedLesson(now()->subDay()->setTime(9, 0), now()->subDay()->setTime(10, 0));
        $this->assertSame(0, Artisan::call('lessons:mark-completed'));
        $this->assertSame('Completed', $booking->fresh()->status);
        $this->assertNull($booking->fresh()->active_lesson_id);
    }

    public function test_future_booked_lesson_stays_booked(): void
    {
        $booking = $this->bookedLesson(now()->addDay()->setTime(9, 0), now()->addDay()->setTime(10, 0));
        $this->assertSame(0, Artisan::call('lessons:mark-completed'));
        $this->assertSame('Booked', $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->active_lesson_id);
    }

    public function test_lesson_that_has_started_but_not_ended_stays_booked(): void
    {
        $booking = $this->bookedLesson(now()->subHour(), now()->addHour());
        $this->assertSame(0, Artisan::call('lessons:mark-completed'));
        $this->assertSame('Booked', $booking->fresh()->status);
    }

    public function test_cancelled_past_lesson_stays_cancelled(): void
    {
        $booking = $this->bookedLesson(now()->subDay()->setTime(9, 0), now()->subDay()->setTime(10, 0), 'Cancelled');
        $this->assertSame(0, Artisan::call('lessons:mark-completed'));
        $this->assertSame('Cancelled', $booking->fresh()->status);
    }

    public function test_booking_on_a_cancelled_lesson_is_never_completed(): void
    {
        $booking = $this->bookedLesson(now()->subDay()->setTime(9, 0), now()->subDay()->setTime(10, 0), 'Booked', true);
        $this->assertSame(0, Artisan::call('lessons:mark-completed'));
        $this->assertSame('Booked', $booking->fresh()->status);
    }

    public function test_command_is_scheduled_daily_in_nepal_time(): void
    {
        $this->artisan('schedule:list')->assertOk()->expectsOutputToContain('lessons:mark-completed');
        // Assert on the registered event itself: dailyAt('02:30') in
        // Asia/Kathmandu, as the Kernel defines it (schedule:list renders
        // the cron converted to the app timezone with table padding, so it
        // is not a stable string to match on).
        $events = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class)->events();
        $event = collect($events)->first(fn ($e) => str_contains((string) $e->command, 'lessons:mark-completed'));
        $this->assertNotNull($event);
        $this->assertSame('30 2 * * *', $event->expression);
        $this->assertSame('Asia/Kathmandu', $event->timezone);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $booking = $this->bookedLesson(now()->subDay()->setTime(9, 0), now()->subDay()->setTime(10, 0));
        $this->assertSame(0, Artisan::call('lessons:mark-completed', ['--dry-run' => true]));
        $this->assertSame('Booked', $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->active_lesson_id);
    }
}
