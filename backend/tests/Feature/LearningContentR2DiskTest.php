<?php

namespace Tests\Feature;

use App\Models\LearningContentImport;
use App\Models\User;
use App\Services\LearningContentDownload;
use App\Services\LocalPdfTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearningContentR2DiskTest extends TestCase
{
    use RefreshDatabase;

    public function test_learning_content_disk_is_a_private_r2_bucket_with_no_public_url(): void
    {
        $disk = config('filesystems.disks.learning-content');

        $this->assertSame('s3', $disk['driver']);
        // No public URL is configured, so no public URL can ever be
        // generated for a review copy.
        $this->assertArrayNotHasKey('url', $disk);
        // No visibility/ACL headers are sent: R2 enforces visibility
        // at the bucket level only, and the bucket is private.
        $this->assertArrayNotHasKey('visibility', $disk);
        // The bucket comes from its own variable, not the public media
        // bucket's AWS_BUCKET.
        $this->assertSame(env('AWS_LEARNING_CONTENT_BUCKET'), $disk['bucket']);
    }

    public function test_learning_content_bucket_comes_from_its_own_env_variable(): void
    {
        $_ENV['AWS_LEARNING_CONTENT_BUCKET'] = 'learn-to-drive-private-test';
        putenv('AWS_LEARNING_CONTENT_BUCKET=learn-to-drive-private-test');
        try {
            $config = require base_path('config/filesystems.php');
            $this->assertSame('learn-to-drive-private-test', $config['disks']['learning-content']['bucket']);
            $this->assertNotSame($config['disks']['s3']['bucket'], $config['disks']['learning-content']['bucket']);
        } finally {
            unset($_ENV['AWS_LEARNING_CONTENT_BUCKET']);
            putenv('AWS_LEARNING_CONTENT_BUCKET');
        }
    }

    public function test_downloaded_private_copy_lands_on_the_learning_content_disk(): void
    {
        Storage::fake('learning-content');
        $item = $this->resource();
        Http::fake([$item->asset_url => Http::response("%PDF-1.4\nfixture", 200, ['Content-Type' => 'application/pdf'])]);

        $file = app(LearningContentDownload::class)->fetch($item);

        $this->assertSame('s3', config('filesystems.disks.learning-content.driver'));
        $this->assertTrue(Storage::disk('learning-content')->exists($file['file_path']));
        $this->assertSame(hash('sha256', "%PDF-1.4\nfixture"), $file['file_hash']);
        $this->assertSame("%PDF-1.4\nfixture", Storage::disk('learning-content')->get($file['file_path']));
    }

    public function test_pdf_tools_stage_a_real_local_copy_from_the_remote_disk(): void
    {
        Storage::fake('learning-content');
        $pdf = "%PDF-1.4\nfixture";
        Storage::disk('learning-content')->put('source.pdf', $pdf);
        $item = $this->resource();
        $item->forceFill(['file_path' => 'source.pdf', 'file_hash' => hash('sha256', $pdf)])->save();

        $path = app(LocalPdfTools::class)->sourcePath($item);

        $this->assertTrue(is_file($path));
        $this->assertSame(hash('sha256', $pdf), hash_file('sha256', $path));
    }

    public function test_pre_move_row_with_a_stale_local_path_degrades_gracefully(): void
    {
        Storage::fake('learning-content');
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        $item = $this->resource();
        // A row created before the move: its file_path pointed at the
        // old local disk, so nothing exists for it on the new disk.
        $item->forceFill(['file_path' => '550e8400-e29b-41d4-a716-446655440000.pdf', 'file_hash' => str_repeat('a', 64)])->save();

        $this->get(route('learningContent.show', $item))->assertOk();
        $this->get(route('learningContent.file', $item))->assertNotFound();
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
}
