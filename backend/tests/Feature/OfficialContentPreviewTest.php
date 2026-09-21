<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\TrafficSign;
use App\Services\OfficialContentPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfficialContentPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_are_deduplicated_and_keep_provenance(): void
    {
        $source = config('official-content.sources.kalanki');
        $html = '<a href="https://giwmscdnone.gov.np/media/example.pdf">Question bank</a>'
            .'<iframe src="https://giwmscdnone.gov.np/media/example.pdf#page=1"></iframe>'
            .'<a href="https://evil.example/questions.pdf">Unapproved</a>'
            .'<a href="https://giwmscdnone.gov.np.evil.example/questions.pdf">Spoof</a>'
            .'<a href="http://giwmscdnone.gov.np/questions.pdf">Insecure</a>'
            .'<a href="https://user:pass@giwmscdnone.gov.np/questions.pdf">Credentials</a>';
        $result = app(OfficialContentPreview::class)->extract($html, $source);
        $this->assertCount(1, $result['assets']);
        $this->assertCount(4, $result['excluded_assets']);
        $this->assertSame($source['url'], $result['assets'][0]['source_url']);
        $this->assertTrue($result['assets'][0]['review_required']);
        $this->assertFalse($result['assets'][0]['asset_downloaded']);
    }

    public function test_sign_images_are_scoped_to_main_and_resolve_relative_urls(): void
    {
        $source = config('official-content.sources.traffic-police');
        $html = '<header><img src="/logo.png"></header><main><img src="/media/signs.jpg" alt="Signs">'
            .'<img src="https://old.example/image.png"></main>';
        $result = app(OfficialContentPreview::class)->extract($html, $source);
        $this->assertCount(1, $result['assets']);
        $this->assertSame('https://traffic.nepalpolice.gov.np/media/signs.jpg', $result['assets'][0]['asset_url']);
        $this->assertCount(1, $result['excluded_assets']);
    }

    public function test_preview_command_saves_a_report_without_creating_content(): void
    {
        Storage::fake('local');
        Http::preventStrayRequests();
        $source = config('official-content.sources.kalanki');
        Http::fake([$source['url'] => Http::response('<iframe src="https://giwmscdnone.gov.np/example.pdf"></iframe>', 200, ['Content-Type' => 'text/html'])]);
        $questions = Question::count();
        $signs = TrafficSign::count();
        $this->artisan('content:preview-official', ['--source' => 'kalanki', '--save' => true])->assertSuccessful();
        $report = json_decode(Storage::disk('local')->get('private/official-content-preview.json'), true);
        $this->assertSame('preview-only', $report['mode']);
        $this->assertCount(1, $report['sources']['kalanki']['assets']);
        $this->assertSame($questions, Question::count());
        $this->assertSame($signs, TrafficSign::count());
        Http::assertSentCount(1);
    }

    public function test_failures_are_reported_and_redirects_are_not_followed(): void
    {
        Storage::fake('local');
        Http::preventStrayRequests();
        $source = config('official-content.sources.kalanki');
        Http::fake([$source['url'] => Http::response('', 302, ['Location' => 'http://127.0.0.1/private'])]);
        $this->artisan('content:preview-official', ['--source' => 'kalanki', '--save' => true])->assertFailed();
        $report = json_decode(Storage::disk('local')->get('private/official-content-preview.json'), true);
        $this->assertSame('failed', $report['sources']['kalanki']['status']);
        Http::assertSentCount(1);
        $this->artisan('content:preview-official', ['--source' => 'https://evil.example'])->assertFailed();
        Http::assertSentCount(1);
    }

    public function test_large_html_is_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        app(OfficialContentPreview::class)->extract(str_repeat('a', 2 * 1024 * 1024 + 1), config('official-content.sources.kalanki'));
    }
}
