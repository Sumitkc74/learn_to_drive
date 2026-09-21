<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\LearningContentImport;
use App\Models\TrafficSign;
use App\Models\User;
use App\Services\LearningContentCollector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearningContentReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_vision_image_requires_review_and_retains_original_image(): void
    {
        Storage::fake('learning-content');
        Storage::fake('public');
        $this->admin();
        $item = $this->resource();
        $image = imagecreatetruecolor(20, 20);
        ob_start(); imagepng($image); $bytes = ob_get_clean(); imagedestroy($image);
        Storage::disk('learning-content')->put('vision.png', $bytes);
        $item->forceFill(['kind' => 'vision-test-image', 'file_path' => 'vision.png',
            'file_hash' => hash('sha256', $bytes), 'mime_type' => 'image/png',
            'file_size' => strlen($bytes), 'downloaded_at' => now()])->save();
        $data = ['testNumber' => 9876, 'confirmed' => 1, 'file_hash' => $item->file_hash];
        $this->get(route('learningContent', ['kind' => 'vision-test-image']))->assertOk()
            ->assertViewHas('sources', fn ($sources) => array_keys($sources) === ['nei-vision']);
        $this->get(route('learningContent.file', ['resource' => $item, 'preview' => 1]))->assertOk();
        $this->post(route('learningContent.storeVision', $item), $data)->assertStatus(409);
        $item->forceFill(['status' => 'Approved'])->save();
        $this->get(route('learningContent.show', $item))->assertOk()->assertSee('Add Individual Vision Test');
        $this->post(route('learningContent.storeVision', $item), array_replace($data, ['confirmed' => 0]))->assertSessionHasErrors('confirmed');
        $this->post(route('learningContent.storeVision', $item), array_replace($data, ['file_hash' => str_repeat('0', 64)]))->assertStatus(409);
        $this->post(route('learningContent.storeVision', $item), $data)->assertRedirect();
        $media = \App\Models\VisionTest::where('testNumber', 9876)->firstOrFail()->getFirstMedia();
        $this->assertSame($bytes, file_get_contents($media->getPath()));
        $this->assertSame($item->source_url, $media->getCustomProperty('source_url'));
        $this->post(route('learningContent.storeVision', $item), array_replace($data, ['testNumber' => 9877]))->assertSessionHasErrors('image');
    }

    public function test_vision_source_excludes_unrelated_images(): void
    {
        $source = config('official-content.sources.nei-vision');
        $result = app(\App\Services\OfficialContentPreview::class)->extract(
            '<img alt="Logo" src="/logo.png"><img alt="Colorplate test" src="/plate.jpg"><img alt="An anomaloscope" src="/equipment.jpg">', $source);
        $this->assertCount(1, $result['assets']);
        $this->assertSame('vision-test-image', $result['assets'][0]['kind']);
        $this->assertSame('https://www.nei.nih.gov/plate.jpg', $result['assets'][0]['asset_url']);
    }

    public function test_content_category_filters_resources_and_available_sources(): void
    {
        $this->admin();
        $question = $this->resource();
        $sign = $question->replicate();
        $sign->forceFill(['kind' => 'traffic-sign-sheet', 'title' => 'Official sign sheet',
            'url_hash' => hash('sha256', 'sign-sheet'), 'asset_url' => 'https://traffic.nepalpolice.gov.np/sign.jpg'])->save();

        $this->get(route('learningContent', ['kind' => 'traffic-sign-sheet']))
            ->assertOk()->assertSee('Official sign sheet')->assertDontSee('Official question collection')
            ->assertViewHas('sources', fn ($sources) => array_keys($sources) === ['traffic-police']);
        $this->get(route('learningContent', ['kind' => 'question-bank-document']))
            ->assertOk()->assertSee('Official question collection')->assertDontSee('Official sign sheet')
            ->assertViewHas('sources', fn ($sources) => !array_key_exists('traffic-police', $sources));
        $this->get(route('learningContent'))->assertOk()
            ->assertSee('Official question collection')->assertSee('Official sign sheet');
    }

    private function resource(): LearningContentImport
    {
        return LearningContentImport::create([
            'source_key' => 'kalanki', 'source_name' => 'Official collection',
            'source_url' => config('official-content.sources.kalanki.url'),
            'asset_url' => 'https://giwmscdnone.gov.np/example.pdf',
            'url_hash' => hash('sha256', 'https://giwmscdnone.gov.np/example.pdf'),
            'kind' => 'question-bank-document', 'title' => 'Official question collection', 'fetched_at' => now(),
        ])->fresh();
    }

    private function admin(): User
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $this->actingAs($admin);

        return $admin;
    }

    private function approval(): array
    {
        return ['decision' => 'Approved', 'title' => 'Reviewed question bank', 'language' => 'English',
            'licence_category' => 'B', 'edition' => 'Unknown', 'review_notes' => 'Edition needs comparison before preparing learner questions.', 'confirmed' => '1'];
    }

    public function test_individual_sign_extraction_validates_crop_and_retains_source(): void
    {
        Storage::fake('learning-content');
        Storage::fake('public');
        $admin = $this->admin();
        $item = $this->resource();
        $image = imagecreatetruecolor(80, 60);
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);
        Storage::disk('learning-content')->put('sheet.png', $bytes);
        $item->forceFill(['kind' => 'traffic-sign-sheet', 'file_path' => 'sheet.png', 'mime_type' => 'image/png', 'file_hash' => hash('sha256', $bytes)])->save();
        $this->get(route('learningContent.extract', $item))->assertOk()->assertSee('Review Individual Sign');
        $data = ['name' => 'Stop', 'nepaliSignName' => 'रोक', 'description' => 'Stop before proceeding.', 'x' => 4, 'y' => 5, 'width' => 30, 'height' => 25, 'confirmed' => 1];
        $this->post(route('learningContent.storeSign', $item), array_replace($data, ['width' => 100]))->assertStatus(422);
        $this->post(route('learningContent.storeSign', $item), array_replace($data, ['confirmed' => 0]))->assertSessionHasErrors('confirmed');
        $this->post(route('learningContent.storeSign', $item), $data)->assertRedirect(route('learningContent.extract', $item));
        $sign = TrafficSign::firstOrFail();
        $this->assertSame($admin->id, $sign->created_by);
        $media = $sign->getFirstMedia();
        $this->assertSame($item->id, $media->getCustomProperty('source_resource_id'));
        $this->assertSame([30, 25], array_slice(getimagesize($media->getPath()), 0, 2));
        $this->post(route('learningContent.storeSign', $item), $data)->assertSessionHasErrors('image');
        $this->assertDatabaseCount('traffic_signs', 1);
        $item->forceFill(['status' => 'Rejected'])->save();
        $this->post(route('learningContent.storeSign', $item), $data)->assertStatus(409);
        $this->get(route('learningContent.extract', $item))->assertNotFound();
        auth()->logout();
        $this->get(route('learningContent.extract', $item))->assertRedirect(route('login'));
        $this->post(route('learningContent.storeSign', $item), $data)->assertRedirect(route('login'));
    }

    public function test_fetch_creates_pending_resources_once_and_preserves_rejections(): void
    {
        $admin = $this->admin();
        Http::preventStrayRequests();
        Http::fake([config('official-content.sources.kalanki.url') => fn () => Http::response('<a href="https://giwmscdnone.gov.np/example.pdf">Question bank</a><iframe src="https://giwmscdnone.gov.np/example.pdf"></iframe>', 200, ['Content-Type' => 'text/html'])]);
        $this->post(route('learningContent.fetch'), ['source' => 'kalanki', 'created_by' => 999, 'status' => 'Approved'])->assertSessionHasNoErrors();
        $item = LearningContentImport::firstOrFail();
        $this->assertSame('Pending', $item->status);
        $this->assertSame($admin->id, $item->created_by);
        $this->post(route('learningContent.review', $item), ['decision' => 'Rejected', 'review_notes' => 'Not the required edition.'])->assertSessionHasNoErrors();
        $result = app(LearningContentCollector::class)->fetch('kalanki');
        $this->assertSame(['added' => 0, 'known' => 1, 'excluded' => 0], $result);
        $this->assertSame('Rejected', $item->fresh()->status);
        $this->assertDatabaseCount('learning_content_imports', 1);
        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('traffic_signs', 0);
        $this->assertDatabaseCount('exam_papers', 0);
    }

    public function test_every_queue_endpoint_requires_an_admin(): void
    {
        $item = $this->resource();
        $this->get(route('learningContent'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'User']));
        foreach (['learningContent' => [], 'learningContent.show' => [$item], 'learningContent.file' => [$item]] as $route => $args) {
            $this->get(route($route, $args))->assertForbidden();
        }
        $this->post(route('learningContent.fetch'), ['source' => 'kalanki'])->assertForbidden();
        $this->post(route('learningContent.download', $item))->assertForbidden();
        $this->post(route('learningContent.review', $item), $this->approval())->assertForbidden();
        $this->get(route('learningContent.extract', $item))->assertForbidden();
        $this->post(route('learningContent.storeSign', $item))->assertForbidden();
    }

    public function test_private_download_records_hash_and_approval_is_audited_without_publishing(): void
    {
        Storage::fake('learning-content');
        $admin = $this->admin();
        $item = $this->resource();
        $pdf = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF";
        Http::preventStrayRequests();
        Http::fake([$item->asset_url => Http::response($pdf, 200, ['Content-Type' => 'application/pdf'])]);
        $this->post(route('learningContent.download', $item))->assertSessionHasNoErrors();
        $item->refresh();
        $this->assertSame(hash('sha256', $pdf), $item->file_hash);
        $this->assertSame(strlen($pdf), $item->file_size);
        Storage::disk('learning-content')->assertExists($item->file_path);
        $this->post(route('learningContent.download', $item))->assertSessionHasNoErrors();
        Http::assertSentCount(1);
        $this->get(route('learningContent.file', $item))->assertOk()->assertDownload('official-resource-'.$item->id.'.pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->post(route('learningContent.review', $item), $this->approval() + ['reviewed_by' => 999, 'file_hash' => 'fake'])->assertSessionHasNoErrors();
        $item->refresh();
        $this->assertSame('Approved', $item->status);
        $this->assertSame($admin->id, $item->reviewed_by);
        $this->assertSame('English', $item->language);
        $this->get(route('learningContent.show', $item))->assertOk()->assertSee('Review Record')->assertDontSee('Approve Reference');
        $this->assertDatabaseHas('audit_logs', ['subject_type' => 'LearningContentImport', 'subject_id' => $item->id, 'actor_id' => $admin->id, 'event' => 'updated']);
        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('traffic_signs', 0);
        $this->assertDatabaseCount('exam_papers', 0);
        $this->post(route('learningContent.review', $item), $this->approval())->assertStatus(409);
        $this->post(route('learningContent.download', $item))->assertStatus(409);
        Http::assertSentCount(1);
    }

    public function test_review_requires_a_file_metadata_and_confirmation(): void
    {
        $this->admin();
        $item = $this->resource();
        $this->post(route('learningContent.review', $item), ['decision' => 'Approved'])->assertSessionHasErrors(['language', 'edition', 'confirmed', 'review_notes']);
        $this->post(route('learningContent.review', $item), $this->approval())->assertStatus(409);
        $this->post(route('learningContent.review', $item), ['decision' => 'Rejected'])->assertSessionHasErrors('review_notes');
        $this->assertSame('Pending', $item->fresh()->status);
    }

    public function test_changed_private_file_cannot_be_approved(): void
    {
        Storage::fake('learning-content');
        $this->admin();
        $item = $this->resource();
        Storage::disk('learning-content')->put('changed.pdf', 'modified bytes');
        $item->forceFill(['file_path' => 'changed.pdf', 'file_hash' => hash('sha256', 'original bytes')])->save();
        $this->post(route('learningContent.review', $item), $this->approval())->assertStatus(409);
        $this->assertSame('Pending', $item->fresh()->status);
    }

    public function test_download_rejects_redirects_wrong_types_and_oversized_responses(): void
    {
        Storage::fake('learning-content');
        $this->admin();
        $item = $this->resource();
        AppSetting::updateOrCreate(['key' => 'document_upload_limit_mb'], ['value' => 1]);
        Http::fake([$item->asset_url => Http::sequence()
            ->push('', 302, ['Location' => 'http://127.0.0.1/private'])
            ->push('<html>Not a PDF</html>', 200, ['Content-Type' => 'application/pdf'])
            ->push('%PDF-'.str_repeat('a', 1024 * 1024), 200)]);
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('learningContent.download', $item))->assertSessionHasErrors('download');
            $this->assertNull($item->fresh()->file_path);
        }
        $this->assertSame([], Storage::disk('learning-content')->allFiles());
    }

    public function test_download_revalidates_hosts_before_any_network_request(): void
    {
        $this->admin();
        $item = $this->resource();
        $item->update(['asset_url' => 'https://giwmscdnone.gov.np.evil.example/file.pdf']);
        Http::preventStrayRequests();
        Http::fake();
        $this->post(route('learningContent.download', $item))->assertSessionHasErrors('download');
        Http::assertNothingSent();
        $this->post(route('learningContent.fetch'), ['source' => 'https://example.com'])->assertSessionHasErrors('source');
    }

    public function test_sign_download_accepts_raster_images_and_rejects_svg(): void
    {
        Storage::fake('learning-content');
        $this->admin();
        $item = $this->resource();
        $item->update(['source_key' => 'traffic-police', 'kind' => 'traffic-sign-sheet', 'asset_url' => 'https://traffic.nepalpolice.gov.np/media/sign.png']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a5XcAAAAASUVORK5CYII=');
        Http::fake([$item->asset_url => Http::sequence()
            ->push('<svg xmlns="http://www.w3.org/2000/svg"></svg>', 200, ['Content-Type' => 'image/png'])
            ->push($png, 200, ['Content-Type' => 'image/png'])]);
        $this->post(route('learningContent.download', $item))->assertSessionHasErrors('download');
        $this->assertNull($item->fresh()->file_path);
        $this->post(route('learningContent.download', $item))->assertSessionHasNoErrors();
        $item->refresh();
        $this->assertSame('image/png', $item->mime_type);
        $this->assertSame(hash('sha256', $png), $item->file_hash);
        $this->get(route('learningContent.show', $item))->assertOk()->assertSee('Click the image to view it at full size.');
        $preview = $this->get(route('learningContent.file', ['resource' => $item, 'preview' => 1]));
        $preview->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith('inline', $preview->headers->get('Content-Disposition'));
        Storage::disk('learning-content')->delete($item->file_path);
        $this->get(route('learningContent.show', $item))->assertOk()->assertSee('Save Private Copy for Review')->assertDontSee('Download Private Copy');
    }

    public function test_queue_and_review_pages_render_filter_and_preserve_pagination_queries(): void
    {
        $this->admin();
        $item = $this->resource();
        $this->get(route('learningContent.show', $item))->assertOk()->assertSee('Approve Reference')->assertSee('Save Private Copy for Review');
        $this->get(route('learningContent', ['search' => 'Official', 'status' => 'Pending', 'per_page' => 25]))->assertOk()
            ->assertSee($item->title)->assertViewHas('imports', fn ($imports) => $imports->total() === 1 && $imports->perPage() === 25);
        $this->get(route('learningContent.show', 99999))->assertNotFound();
        $this->get(route('learningContent.file', $item))->assertNotFound();
        $this->get(route('adminDashboard'))->assertSee('Learning Content');
    }

    public function test_cli_collection_records_system_attribution_and_rejects_unknown_sources(): void
    {
        Http::preventStrayRequests();
        Http::fake([config('official-content.sources.kalanki.url') => Http::response('<iframe src="https://giwmscdnone.gov.np/cli.pdf"></iframe>', 200, ['Content-Type' => 'text/html'])]);
        $this->artisan('content:fetch-official', ['--source' => 'kalanki'])->assertSuccessful();
        $this->assertDatabaseHas('learning_content_imports', ['asset_url' => 'https://giwmscdnone.gov.np/cli.pdf', 'status' => 'Pending', 'created_by' => null]);
        $this->artisan('content:fetch-official', ['--source' => 'https://example.com'])->assertFailed();
        Http::assertSentCount(1);
    }
}
