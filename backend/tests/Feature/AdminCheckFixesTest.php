<?php

namespace Tests\Feature;

use App\Jobs\ScreenImportedContent;
use App\Models\DrivingLesson;
use App\Models\DrivingSchool;
use App\Models\GovernmentNoticeImport;
use App\Models\LearningContentImport;
use App\Models\LessonBooking;
use App\Models\LessonVehicle;
use App\Models\SchoolLessonPayment;
use App\Models\TrafficSign;
use App\Models\User;
use App\Services\ContentAiScreening;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminCheckFixesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('is_seed_admin', true)->firstOrFail();
    }

    private function notice(array $overrides = []): GovernmentNoticeImport
    {
        return GovernmentNoticeImport::create(array_merge([
            'source_name' => 'Official', 'source_domain' => 'example.org',
            'source_url' => 'https://example.org/notice', 'title' => 'Notice',
            'content_hash' => hash('sha256', 'notice-'.Str::random(8)),
            'status' => 'Pending', 'fetched_at' => now(),
        ], $overrides));
    }

    public function test_screening_failure_records_the_real_gemini_error(): void
    {
        config(['pdf-translation.gemini_key' => 'fake', 'pdf-translation.gemini_model' => 'gemini-test', 'content-screening.daily_limit' => 100]);
        $record = $this->notice();
        $page = new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response(200, [], 'Driving licence notice'));
        $this->mock(\App\Services\PublicWebsiteRequest::class)->shouldReceive('get')->once()->andReturn($page);
        Http::fake(['*' => Http::response(['error' => ['code' => 400, 'message' => 'API key not valid. Please pass a valid API key.', 'status' => 'INVALID_ARGUMENT']], 400)]);

        app(ContentAiScreening::class)->screen($record);

        $fresh = $record->fresh();
        $this->assertSame('Failed', $fresh->ai_status);
        $this->assertStringContainsString('Screening failed.', $fresh->ai_report);
        $this->assertStringContainsString('API key not valid', $fresh->ai_report);
        $this->assertStringContainsString('HTTP 400', $fresh->ai_report);
    }

    public function test_screening_without_a_key_names_the_missing_key(): void
    {
        config(['pdf-translation.gemini_key' => null]);
        $record = $this->notice();

        app(ContentAiScreening::class)->screen($record);

        $this->assertSame('Failed', $record->fresh()->ai_status);
        $this->assertStringContainsString('not configured', $record->fresh()->ai_report);
    }

    public function test_dashboard_failed_ai_count_matches_the_lists(): void
    {
        $this->notice()->forceFill(['ai_status' => 'Failed'])->save();
        $this->notice(['title' => 'Rejected notice', 'status' => 'Rejected'])->forceFill(['ai_status' => 'Failed'])->save();
        LearningContentImport::create(['source_key' => 'kalanki', 'source_name' => 'Official', 'source_url' => 'https://example.org', 'asset_url' => 'https://example.org/file.pdf', 'url_hash' => hash('sha256', 'lc'), 'title' => 'Test', 'kind' => 'question-bank-document', 'fetched_at' => now()])->forceFill(['ai_status' => 'Failed'])->save();
        LearningContentImport::create(['source_key' => 'other', 'source_name' => 'Official', 'source_url' => 'https://example.org/2', 'asset_url' => 'https://example.org/file2.pdf', 'url_hash' => hash('sha256', 'lc2'), 'title' => 'Test 2', 'kind' => 'question-bank-document', 'fetched_at' => now()])->forceFill(['ai_status' => 'Ready'])->save();

        $this->actingAs($this->admin())->get(route('adminDashboard'))->assertOk()
            ->assertSee('Failed AI checks: 3');
    }

    public function test_sidebar_links_school_payments_and_search_finds_schools(): void
    {
        DrivingSchool::create(['name' => 'Nepal Driving Center', 'address' => 'Chabahil, Kathmandu', 'phone' => '+97714586995', 'status' => 'Pending']);
        DrivingSchool::create(['name' => 'Bagmati Driving Center', 'address' => 'Dyabu Marg, Kathmandu', 'phone' => '+97714022567', 'status' => 'Approved']);

        $this->actingAs($this->admin())->get(route('adminDashboard'))->assertOk()
            ->assertSee(route('admin.school-payments'), false)
            ->assertSee('School Payments');
        $this->get(route('adminSearch', ['q' => 'Nepal Driving Center']))->assertOk()
            ->assertSee('Driving Schools')
            ->assertSee('Nepal Driving Center');
        $this->get(route('admin.schools.index', ['search' => 'Nepal']))->assertOk()
            ->assertSee('Nepal Driving Center')
            ->assertDontSee('Bagmati Driving Center');
    }

    public function test_dashboard_shows_schools_bookings_and_payments_counts(): void
    {
        $admin = $this->admin();
        $school = DrivingSchool::create(['name' => 'Nepal Driving Center', 'address' => 'Chabahil', 'phone' => '+97714586995', 'status' => 'Pending']);
        DrivingSchool::create(['name' => 'Bagmati Driving Center', 'address' => 'Dyabu Marg', 'phone' => '+97714022567', 'status' => 'Approved']);
        $vehicle = LessonVehicle::create(['driving_school_id' => $school->id, 'instructor_id' => $admin->id, 'name' => 'Training sedan', 'registration' => 'BA 1 KHA 1234', 'transmission' => 'Manual']);
        $lesson = DrivingLesson::create(['lesson_vehicle_id' => $vehicle->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'price_npr' => 1500, 'meeting_point' => 'Chabahil']);
        $booking = LessonBooking::create(['driving_lesson_id' => $lesson->id, 'learner_id' => $admin->id, 'status' => 'Booked']);
        (new SchoolLessonPayment)->forceFill(['id' => (string) Str::uuid(), 'lesson_booking_id' => $booking->id, 'amount_paisa' => 150000, 'gateway' => 'khalti', 'live' => false, 'status' => 'Paid'])->save();

        $this->actingAs($admin)->get(route('adminDashboard'))->assertOk()
            ->assertSee('Driving schools')
            ->assertSee('Pending schools')
            ->assertSee('Lesson bookings')
            ->assertSee('School lesson payments')
            ->assertViewHas('operations', fn ($o) => $o['schools'] === 2 && $o['schools_pending'] === 1 && $o['lesson_bookings'] === 1 && $o['school_payments'] === 1 && $o['school_paid_paisa'] === 150000);
    }

    public function test_retry_requeues_a_failed_screening(): void
    {
        Queue::fake();
        $record = $this->notice();
        $record->forceFill(['ai_status' => 'Failed'])->save();

        $this->actingAs($this->admin())->post(route('contentScreening.retry', ['notice', $record->id]))
            ->assertRedirect();

        $this->assertSame('Queued', $record->fresh()->ai_status);
        Queue::assertPushed(ScreenImportedContent::class);
    }

    public function test_review_operations_shows_screening_failures(): void
    {
        $this->notice()->forceFill(['ai_status' => 'Failed', 'ai_report' => "Screening failed. Check Gemini configuration.\n\nTechnical detail: Gemini API error (HTTP 400): bad key."])->save();

        $this->actingAs($this->admin())->get(route('reviewOperations', ['type' => 'government']))->assertOk()
            ->assertSee('AI screening')
            ->assertSee('Failed')
            ->assertSee('Gemini API error (HTTP 400): bad key.');
    }

    public function test_settings_show_read_only_configuration(): void
    {
        $this->actingAs($this->admin())->get(route('appSettings'))->assertOk()
            ->assertSee('Configuration')
            ->assertSee('Premium price')
            ->assertSee('Premium payments')
            ->assertSee('School lesson payments')
            ->assertSee('Gemini API key')
            ->assertSee('Gemini model');
    }

    public function test_traffic_sign_edit_title_is_spelled_correctly(): void
    {
        $sign = TrafficSign::create(['name' => 'Stop and give way', 'nepaliSignName' => 'रोक्नुहोस्', 'description' => 'Stop.', 'image' => 'demo']);

        $this->actingAs($this->admin())->get(route('editTrafficSign', $sign->id))->assertOk()
            ->assertSee('Edit Traffic Signs')
            ->assertDontSee('Trafic-Signs');
    }
}
