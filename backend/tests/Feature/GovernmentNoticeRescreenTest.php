<?php

namespace Tests\Feature;

use App\Jobs\ScreenImportedContent;
use App\Models\GovernmentNoticeImport;
use App\Models\LearningContentImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GovernmentNoticeRescreenTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'Admin']);
    }

    private function notice(array $overrides = []): GovernmentNoticeImport
    {
        return GovernmentNoticeImport::create(array_merge([
            'source_name' => 'Official', 'source_domain' => 'dotm.gov.np',
            'source_url' => 'https://dotm.gov.np/notice', 'title' => 'Notice',
            'content_hash' => hash('sha256', 'notice-'.\Illuminate\Support\Str::random(8)),
            'status' => 'Pending', 'fetched_at' => now(),
        ], $overrides));
    }

    public function test_retry_flagged_requeues_only_pending_flagged_imports(): void
    {
        Queue::fake();
        $flagged = $this->notice();
        $flagged->forceFill(['ai_status' => 'Flagged'])->save();
        $failed = $this->notice();
        $failed->forceFill(['ai_status' => 'Failed'])->save();
        $approved = $this->notice(['status' => 'Approved']);
        $approved->forceFill(['ai_status' => 'Flagged'])->save();
        $learning = LearningContentImport::create(['source_key' => 'kalanki', 'source_name' => 'Official', 'source_url' => 'https://example.org', 'asset_url' => 'https://example.org/file.pdf', 'url_hash' => hash('sha256', 'learning'), 'title' => 'Test', 'kind' => 'question-bank-document', 'fetched_at' => now()]);
        $learning->forceFill(['ai_status' => 'Flagged'])->save();

        $this->actingAs($this->admin())->post(route('contentScreening.retryFlagged'))->assertRedirect();

        $this->assertSame('Queued', $flagged->fresh()->ai_status);
        $this->assertSame('Failed', $failed->fresh()->ai_status);
        $this->assertSame('Flagged', $approved->fresh()->ai_status);
        $this->assertSame('Queued', $learning->fresh()->ai_status);
        Queue::assertPushed(ScreenImportedContent::class, 2);
    }

    public function test_retry_flagged_button_shows_on_government_imports_page(): void
    {
        $record = $this->notice();
        $record->forceFill(['ai_status' => 'Flagged'])->save();

        $response = $this->actingAs($this->admin())->get(route('governmentNotices'))->assertOk();

        $response->assertSee('Retry 1 flagged AI screening(s)');
        $response->assertSee(route('contentScreening.retryFlagged'), false);
    }

    public function test_government_imports_page_shows_pending_only_screening_counts(): void
    {
        Queue::fake();
        $pending = $this->notice();
        $pending->forceFill(['ai_status' => 'Flagged'])->save();
        $approved = $this->notice(['status' => 'Approved']);
        $approved->forceFill(['ai_status' => 'Flagged'])->save();

        $response = $this->actingAs($this->admin())->get(route('governmentNotices'))->assertOk();

        // The approved import keeps its Flagged ai_status but is no longer
        // awaiting decision, so it must not inflate the counts or the button.
        $response->assertSee('AI screening (awaiting decision):', false);
        $response->assertSee('1 Flagged', false);
        $response->assertSee('(1 awaiting decision)', false);
        $response->assertSee('Retry 1 flagged AI screening(s)', false);
        $response->assertDontSee('Retry 2 flagged AI screening(s)', false);

        // The button number matches what retryFlagged actually queues.
        $this->actingAs($this->admin())->post(route('contentScreening.retryFlagged'))->assertRedirect();
        Queue::assertPushed(ScreenImportedContent::class, 1);
        $this->assertSame('Queued', $pending->fresh()->ai_status);
        $this->assertSame('Flagged', $approved->fresh()->ai_status);
    }
}
