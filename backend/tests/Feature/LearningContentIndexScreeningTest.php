<?php

namespace Tests\Feature;

use App\Models\LearningContentImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The learning-content queue shows the same AI screening counts line
 * and bulk retry buttons as the government queue, Pending-scoped.
 * The retry routes already queue learning items (see
 * ContentScreeningController::retryFailed / retryFlagged, which loop
 * over both kinds), so this is UI-only — but the test also proves a
 * retry click actually queues a learning item.
 */
class LearningContentIndexScreeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_pending_screening_counts_and_both_retry_buttons(): void
    {
        $this->admin();
        $this->resource(['ai_status' => 'Failed']);
        $this->resource(['ai_status' => 'Flagged']);
        $this->resource(['ai_status' => 'Ready']);
        // Reviewed rows keep their last ai_status but must not count:
        // the line covers the review queue (Pending) only.
        $this->resource(['status' => 'Approved', 'ai_status' => 'Failed']);

        $this->get(route('learningContent'))
            ->assertOk()
            ->assertSee('AI screening (awaiting decision): 1 Failed · 1 Flagged · 1 Ready · 0 Deferred · 0 in progress · 0 not screened (3 awaiting decision)')
            ->assertSee('Retry 1 failed AI screening(s)')
            ->assertSee('Retry 1 flagged AI screening(s)');
    }

    public function test_retry_failed_button_queues_the_pending_learning_item(): void
    {
        $this->admin();
        $failed = $this->resource(['ai_status' => 'Failed']);
        Queue::fake();

        $this->post(route('contentScreening.retryFailed'))
            ->assertRedirect()
            ->assertSessionHas('success', 'Queued 1 failed AI screening(s) for another attempt. Human approval is still required.');

        $this->assertSame('Queued', $failed->fresh()->ai_status);
        Queue::assertPushed(\App\Jobs\ScreenImportedContent::class, fn ($job) => $job->kind === 'learning' && $job->id === $failed->id);
    }

    private function resource(array $overrides = []): LearningContentImport
    {
        $item = LearningContentImport::create([
            'source_key' => 'kalanki', 'source_name' => 'Official collection',
            'source_url' => config('official-content.sources.kalanki.url'),
            'asset_url' => 'https://giwmscdnone.gov.np/example-'.uniqid().'.pdf',
            'url_hash' => hash('sha256', 'https://giwmscdnone.gov.np/example-'.uniqid().'.pdf'),
            'kind' => 'question-bank-document', 'title' => 'Official question collection', 'fetched_at' => now(),
        ]);
        $item->forceFill($overrides)->save();

        return $item->fresh();
    }

    private function admin(): User
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $this->actingAs($admin);

        return $admin;
    }
}
