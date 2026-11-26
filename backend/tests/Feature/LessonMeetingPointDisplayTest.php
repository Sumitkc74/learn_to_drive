<?php
namespace Tests\Feature;

use App\Models\{DrivingLesson, DrivingSchool, LessonVehicle, SchoolMembership, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonMeetingPointDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function lesson(string $meetingPoint, string $schoolName = 'Bagmati Driving Center'): DrivingLesson
    {
        $teacher = User::factory()->create(['is_active' => true]);
        $school = DrivingSchool::create(['name' => $schoolName, 'status' => 'Approved']);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $teacher->id, 'role' => 'Instructor']);
        $car = LessonVehicle::create(['driving_school_id' => $school->id, 'instructor_id' => $teacher->id,
            'name' => 'Swift', 'registration' => 'TEST-'.$teacher->id, 'transmission' => 'Automatic']);
        return DrivingLesson::create(['lesson_vehicle_id' => $car->id, 'starts_at' => now()->addDays(2)->setTime(9, 0),
            'ends_at' => now()->addDays(2)->setTime(10, 0), 'price_npr' => 1000, 'meeting_point' => $meetingPoint, 'cancelled' => false]);
    }

    public function test_card_shows_school_name_once_when_meeting_point_repeats_it(): void
    {
        $this->lesson('Bagmati Driving Center, Dyabu Marg, Kathmandu');
        $this->get('/learn/driving-lessons')->assertOk()
            ->assertDontSee('Bagmati Driving Center · Bagmati Driving Center')
            ->assertSee('Bagmati Driving Center · Dyabu Marg, Kathmandu');
    }

    public function test_meeting_point_without_school_name_renders_unchanged(): void
    {
        $this->lesson('School reception');
        $this->get('/learn/driving-lessons')->assertOk()
            ->assertSee('Bagmati Driving Center · School reception');
    }

    public function test_meeting_point_equal_to_school_name_shows_name_only(): void
    {
        $lesson = $this->lesson('Bagmati Driving Center');
        $this->assertSame('', $lesson->displayMeetingPoint());
        $this->get('/learn/driving-lessons')->assertOk()
            ->assertDontSee('Bagmati Driving Center · Bagmati Driving Center');
    }

    public function test_display_meeting_point_strips_each_separator(): void
    {
        $this->assertSame('Dyabu Marg, Kathmandu', $this->lesson('Bagmati Driving Center, Dyabu Marg, Kathmandu')->displayMeetingPoint());
        $this->assertSame('Dyabu Marg', $this->lesson('Bagmati Driving Center - Dyabu Marg')->displayMeetingPoint());
        $this->assertSame('Dyabu Marg', $this->lesson('Bagmati Driving Center · Dyabu Marg')->displayMeetingPoint());
        $this->assertSame('School reception', $this->lesson('School reception')->displayMeetingPoint());
        $this->assertSame('Somewhere else, Bagmati Driving Center', $this->lesson('Somewhere else, Bagmati Driving Center')->displayMeetingPoint());
    }
}
