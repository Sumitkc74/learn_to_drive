<?php

namespace Tests\Feature;

use App\Models\LearningContentImport;
use App\Models\User;
use App\Services\ContentAiScreening;
use App\Support\LearningContentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The learning-content disk cannot be resolved when
 * AWS_LEARNING_CONTENT_BUCKET is unset (the S3 adapter constructor
 * requires a string bucket). Every admin review action that touches a
 * saved copy must degrade gracefully instead of 500ing: the detail
 * page renders with a warning, file actions refuse with the fix-it
 * message, and screening records Failed naming the storage problem.
 *
 * The disk is deliberately NOT faked here: faking it would hide the
 * exact failure this test guards against.
 */
class LearningContentStorageUnavailableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Simulate the live misconfiguration: no bucket configured.
        config(['filesystems.disks.learning-content.bucket' => null]);
    }

    public function test_detail_page_renders_with_a_storage_warning_instead_of_a_500(): void
    {
        $this->admin();
        $item = $this->resource();
        $this->assertNull(LearningContentStorage::disk());

        $this->get(route('learningContent.show', $item))
            ->assertOk()
            ->assertSee('Learning-content storage is not configured')
            ->assertSee('AWS_LEARNING_CONTENT_BUCKET')
            ->assertSee('Save a private copy to preview it here.')
            ->assertDontSee('review.pdf');
    }

    public function test_save_private_copy_refuses_with_the_storage_message_instead_of_a_500(): void
    {
        $this->admin();
        $item = $this->resource();

        $this->post(route('learningContent.download', $item))
            ->assertRedirect()
            ->assertSessionHasErrors(['download' => LearningContentStorage::UNAVAILABLE_MESSAGE]);
    }

    public function test_file_preview_aborts_with_the_storage_message_instead_of_a_500(): void
    {
        $this->admin();
        $item = $this->resource();

        $this->get(route('learningContent.file', $item))->assertStatus(503);
        $this->get(route('learningContent.file', ['resource' => $item, 'inline' => 1]))->assertStatus(503);
    }

    public function test_screening_records_failed_naming_the_storage_configuration(): void
    {
        $item = $this->resource();

        app(ContentAiScreening::class)->screen($item);

        $item = $item->fresh();
        $this->assertSame('Failed', $item->ai_status);
        $this->assertStringContainsString('AWS_LEARNING_CONTENT_BUCKET', (string) $item->ai_report);
        $this->assertStringContainsString('Learning-content storage is not configured', (string) $item->ai_report);
    }

    public function test_approve_without_storage_is_refused_with_the_storage_message(): void
    {
        $this->admin();
        $item = $this->resource();
        $item->forceFill(['ai_status' => 'Ready', 'ai_hash' => app(ContentAiScreening::class)->fingerprint($item)])->save();

        $this->post(route('learningContent.review', $item), [
            'decision' => 'Approved', 'title' => 'Reviewed question bank', 'language' => 'English',
            'licence_category' => 'B', 'edition' => 'Unknown',
            'review_notes' => 'Inspected the saved file.', 'confirmed' => '1',
        ])->assertStatus(503);

        $this->assertSame('Pending', $item->fresh()->status);
    }

    private function resource(): LearningContentImport
    {
        $item = LearningContentImport::create([
            'source_key' => 'kalanki', 'source_name' => 'Official collection',
            'source_url' => config('official-content.sources.kalanki.url'),
            'asset_url' => 'https://giwmscdnone.gov.np/example.pdf',
            'url_hash' => hash('sha256', 'https://giwmscdnone.gov.np/example.pdf'),
            'kind' => 'question-bank-document', 'title' => 'Official question collection', 'fetched_at' => now(),
        ]);
        // A row with a saved copy, as live items #1, #5 and #11 have.
        $item->forceFill([
            'file_path' => '550e8400-e29b-41d4-a716-446655440000.pdf',
            'file_hash' => str_repeat('a', 64),
            'mime_type' => 'application/pdf',
            'file_size' => 12345,
            'downloaded_at' => now(),
        ])->save();

        return $item->fresh();
    }

    private function admin(): User
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $this->actingAs($admin);

        return $admin;
    }
}
