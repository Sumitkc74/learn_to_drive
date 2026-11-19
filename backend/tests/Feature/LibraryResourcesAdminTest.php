<?php

namespace Tests\Feature;

use App\Jobs\ScreenImportedContent;
use App\Models\ExamInformation;
use App\Models\GovernmentNoticeImport;
use App\Models\LearningContentImport;
use App\Models\Notice;
use App\Models\Tutorial;
use App\Models\User;
use App\Models\VisionTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryResourcesAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'Admin']);
    }

    public function test_admin_can_create_tutorial_with_youtube_link(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('insertTutorial'), [
            'title' => 'How the Nepal driving licence process works',
            'description' => 'Apply through your province transport portal.',
            'videoLink' => 'https://www.youtube.com/watch?v=s0kZb1q0EzU',
            'image' => UploadedFile::fake()->image('thumb.png'),
        ])->assertRedirect(route('allTutorial'));

        $tutorial = Tutorial::where('title', 'How the Nepal driving licence process works')->firstOrFail();
        $this->assertSame('https://www.youtube.com/watch?v=s0kZb1q0EzU', $tutorial->videoLink);
        $this->assertTrue($tutorial->hasMedia());

        $this->get(route('learn.detail', ['tutorial', $tutorial->id]))
            ->assertOk()
            ->assertSee('https://www.youtube.com/watch?v=s0kZb1q0EzU', false)
            ->assertSee('Watch tutorial');
    }

    public function test_admin_can_create_vision_test_with_image(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('insertVisionTest'), [
            'testNumber' => 12,
            'image' => UploadedFile::fake()->image('vision-plate-12.png'),
        ])->assertRedirect(route('allVisionTest'));

        $test = VisionTest::where('testNumber', 12)->firstOrFail();
        $this->assertSame('vision-plate-12.png', $test->image);
        $this->assertTrue($test->hasMedia());

        $this->getJson('/api/visionTest')->assertOk()
            ->assertJsonPath('data.visionTests.0.testNumber', '12'); // testNumber is a string column

        $this->get(route('learn.detail', ['vision-test', $test->id]))
            ->assertOk()
            ->assertSee('Reveal the number');
    }

    public function test_admin_can_create_exam_information_with_pdf(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('insertExamInformation'), [
            'name' => 'Licence categories A and B',
            'description' => 'Category A is for motorcycles and scooters.',
            'language' => 'English',
            'pdf' => UploadedFile::fake()->createWithContent('info.pdf', "%PDF-1.4\n%%EOF"),
        ])->assertRedirect(route('allExamInformation'));

        $info = ExamInformation::where('name', 'Licence categories A and B')->firstOrFail();
        $this->assertNotNull($info->pdfMedia());
        $this->assertSame('pdf', $info->pdfMedia()->extension);

        $this->get(route('learn.detail', ['exam-information', $info->id]))
            ->assertOk()
            ->assertSee('Open PDF')
            ->assertDontSee('The document is currently unavailable.');
    }

    public function test_admin_can_retry_all_failed_screenings(): void
    {
        Queue::fake();
        config(['content-screening.enabled' => false]); // creation must not dispatch; only the bulk retry may
        $admin = $this->admin();

        $failedNotices = [];
        for ($i = 0; $i < 2; $i++) {
            $failedNotices[] = GovernmentNoticeImport::create([
                'source_name' => 'Official', 'source_domain' => 'dotm.gov.np',
                'source_url' => "https://dotm.gov.np/test-{$i}", 'title' => "Test {$i}",
                'content_hash' => hash('sha256', "test-{$i}"), 'status' => 'Pending', 'fetched_at' => now(),
            ]);
            $failedNotices[$i]->forceFill(['ai_status' => 'Failed', 'ai_retry_count' => 3, 'ai_activity_at' => now()->subDay()])->save();
        }
        $failedLearning = LearningContentImport::create([
            'source_key' => 'test-sheet', 'source_name' => 'Official',
            'kind' => 'traffic-sign-sheet', 'title' => 'Sign sheet',
            'source_url' => 'https://example.com/sheet.pdf',
            'asset_url' => 'https://example.com/sheet.pdf',
            'url_hash' => hash('sha256', 'test-sheet'),
            'fetched_at' => now(),
        ]);
        $failedLearning->forceFill(['ai_status' => 'Failed', 'ai_retry_count' => 3, 'ai_activity_at' => now()->subDay()])->save();
        $ready = GovernmentNoticeImport::create([
            'source_name' => 'Official', 'source_domain' => 'dotm.gov.np',
            'source_url' => 'https://dotm.gov.np/ready', 'title' => 'Ready',
            'content_hash' => hash('sha256', 'ready'), 'status' => 'Pending', 'fetched_at' => now(),
        ]);
        $ready->forceFill(['ai_status' => 'Ready', 'ai_retry_count' => 0])->save();

        $this->actingAs($admin)->get(route('governmentNotices'))->assertOk()
            ->assertSee('Retry 2 failed AI screening');

        $this->actingAs($admin)->post(route('contentScreening.retryFailed'))
            ->assertRedirect();

        foreach ($failedNotices as $record) {
            $this->assertSame('Queued', $record->fresh()->ai_status);
            $this->assertSame(0, $record->fresh()->ai_retry_count);
        }
        $this->assertSame('Queued', $failedLearning->fresh()->ai_status);
        $this->assertSame(0, $failedLearning->fresh()->ai_retry_count);
        $this->assertSame('Ready', $ready->fresh()->ai_status);
        Queue::assertPushed(ScreenImportedContent::class, 3);
    }

    public function test_bulk_retry_keeps_the_three_automatic_retry_cap(): void
    {
        Queue::fake();
        config(['content-screening.enabled' => false]);
        $admin = $this->admin();

        $record = GovernmentNoticeImport::create([
            'source_name' => 'Official', 'source_domain' => 'dotm.gov.np',
            'source_url' => 'https://dotm.gov.np/cap', 'title' => 'Cap',
            'content_hash' => hash('sha256', 'cap'), 'status' => 'Pending', 'fetched_at' => now(),
        ]);
        $record->forceFill(['ai_status' => 'Failed', 'ai_retry_count' => 3, 'ai_activity_at' => now()->subDay()])->save();

        $this->actingAs($admin)->post(route('contentScreening.retryFailed'))->assertRedirect();
        $this->assertSame(0, $record->fresh()->ai_retry_count);

        // Fail it again; the automatic recoverer may retry at most three times.
        $record->forceFill(['ai_status' => 'Running', 'ai_activity_at' => now()->subMinutes(11)])->save();
        for ($i = 0; $i < 4; $i++) {
            $this->travel(11)->minutes();
            $this->artisan('content:recover-screening')->assertSuccessful();
        }
        $this->assertSame(3, $record->fresh()->ai_retry_count);
        $this->assertSame('Failed', $record->fresh()->ai_status);
        Queue::assertPushed(ScreenImportedContent::class, 4); // 1 bulk + 3 automatic
    }

    public function test_non_admin_cannot_retry_failed_screenings(): void
    {
        $user = User::factory()->create(['role' => 'User']);
        $this->actingAs($user)->post(route('contentScreening.retryFailed'))->assertForbidden();
    }

    public function test_learner_notices_list_shows_published_notice_only(): void
    {
        Notice::create(['title' => 'New licence office hours', 'nepaliTitle' => 'नयाँ लाइसेन्स कार्यालय समय', 'description' => 'The office opens at 10.', 'nepaliDescription' => 'कार्यालय बिहान १० बजे खुल्छ।', 'status' => 'Published']);
        Notice::create(['title' => 'Draft notice', 'nepaliTitle' => 'मस्यौदा सूचना', 'description' => 'Not yet published.', 'nepaliDescription' => 'अझै प्रकाशित भएको छैन।', 'status' => 'Draft']);

        $this->get(route('learn.library', 'notice'))->assertOk()
            ->assertSee('New licence office hours')
            ->assertDontSee('Draft notice');
    }
}
