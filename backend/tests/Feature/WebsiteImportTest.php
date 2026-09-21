<?php
namespace Tests\Feature;
use App\Models\LearningContentImport;
use App\Models\User;
use App\Services\PublicWebsiteRequest;
use App\Services\LearningContentDownload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class WebsiteImportTest extends TestCase {
    use RefreshDatabase;
    public function test_scan_select_and_download_uses_verified_addresses_and_preserves_reviews(): void {
        Storage::fake('learning-content');
        $this->actingAs(User::factory()->create(['role'=>'Admin']));
        $web = $this->partialMock(PublicWebsiteRequest::class);
        $web->shouldReceive('addresses')->with('example.org')->andReturn(['93.184.215.14']);
        Http::fake(function ($request, $options) {
            $this->assertFalse($options['allow_redirects']);
            $this->assertSame(['example.org:443:93.184.215.14'], $options['curl'][CURLOPT_RESOLVE]);
            $this->assertSame('', $options['proxy']);
            return str_ends_with($request->url(), '.pdf') ? Http::response("%PDF-1.4\n%%EOF",200,['Content-Type'=>'application/pdf'])
                : Http::response('<a href="/bank.pdf">Question bank</a><a href="/bank.pdf">Question bank</a><a href="http://127.0.0.1/private.pdf">Private</a>',200,['Content-Type'=>'text/html']);
        });
        $this->post(route('websiteImport.scan'), ['url'=>'https://example.org/resources','kind'=>'question-bank-document'])->assertRedirect();
        $scan = session('website_import_scan');
        $this->assertCount(1,$scan['assets']);
        $this->get(route('websiteImport'))->assertOk()->assertSee('Question bank');
        $data = ['token'=>$scan['token'],'selected'=>[0]];
        $this->post(route('websiteImport.store'),$data)->assertRedirect();
        $resource = LearningContentImport::firstOrFail();
        $this->assertSame('Pending',$resource->status);
        $this->assertSame('custom-website',$resource->source_key);
        $file = app(LearningContentDownload::class)->fetch($resource);
        Storage::disk('learning-content')->assertExists($file['file_path']);
        $resource->forceFill(['status'=>'Rejected'])->save();
        $this->post(route('websiteImport.store'),$data)->assertRedirect();
        $this->assertDatabaseCount('learning_content_imports',1);
        $this->assertSame('Rejected',$resource->fresh()->status);
        $this->post(route('websiteImport.store'),['token'=>$scan['token'],'selected'=>[99]])->assertStatus(422);
        $this->post(route('websiteImport.store'),['token'=>'forged','selected'=>[0]])->assertStatus(419);
    }
    public function test_private_dns_addresses_are_blocked_before_network_access(): void {
        Http::preventStrayRequests();
        foreach (['127.0.0.1','10.0.0.1','169.254.169.254','100.64.0.1','224.0.0.1','198.18.0.1'] as $ip) {
            $web = new class($ip) extends PublicWebsiteRequest {
                public function __construct(private string $ip) {}
                public function addresses(string $host): array { return [$this->ip]; }
            };
            try { $web->get('https://example.org/page'); $this->fail('Unsafe address accepted'); }
            catch (\RuntimeException $e) { $this->assertStringContainsString('network addresses',$e->getMessage()); }
        }
        Http::assertNothingSent();
    }
    public function test_custom_image_discovery_is_admin_only_and_does_not_fetch_assets(): void {
        $this->actingAs(User::factory()->create(['role'=>'User']))->get(route('websiteImport'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role'=>'Admin']));
        $this->partialMock(PublicWebsiteRequest::class)->shouldReceive('addresses')->andReturn(['93.184.215.14']);
        Http::fake(fn()=>Http::response('<img alt="Sign" data-src="https://cdn.example.org/sign.png"><img src="/logo.svg"><img src="https://localhost/private.png">',200,['Content-Type'=>'text/html']));
        $this->post(route('websiteImport.scan'),['url'=>'https://example.org/images','kind'=>'traffic-sign-sheet'])->assertRedirect();
        $this->assertCount(1,session('website_import_scan.assets'));
        Http::assertSentCount(1);
        $this->assertDatabaseCount('learning_content_imports',0);
    }
}
