<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Services\DotmNoticeImporter;

class BrowserBoundaryTest extends TestCase
{
    use RefreshDatabase;
    public function test_untrusted_host_is_rejected_and_pages_cannot_be_framed(): void
    {
        $this->get('https://untrusted.example/learn/login')->assertStatus(400);
        $this->get('/learn/login')->assertOk()->assertHeader('X-Frame-Options','DENY')->assertHeader('Content-Security-Policy',"frame-ancestors 'none'");
        $this->assertSame(parse_url(config('app.url'),PHP_URL_HOST),parse_url(route('learn.password.reset',['token'=>'placeholder']),PHP_URL_HOST));
    }
    public function test_notice_scraper_rejects_redirects_and_oversized_bodies(): void
    {
        foreach([Http::response('',302,['Location'=>'http://127.0.0.1/']),Http::response(str_repeat('x',2*1024*1024+1))] as $response){
            Http::fake(function($request,$options)use($response){
                $this->assertFalse($options['allow_redirects']);
                $this->assertTrue($options['stream']);
                return $response;
            });
            try{app(DotmNoticeImporter::class)->fetch();$this->fail('Unsafe response accepted.');}
            catch(\RuntimeException $e){$this->assertMatchesRegularExpression('/redirect|2 MB/',$e->getMessage());}
        }
    }
    public function test_web_login_is_limited_per_account_across_ips(): void
    {
        for($i=1;$i<=5;$i++)$this->withServerVariables(['REMOTE_ADDR'=>'198.51.100.'.$i])->post('/learn/login',['email'=>'limited@example.test','password'=>'wrong'])->assertRedirect();
        $this->withServerVariables(['REMOTE_ADDR'=>'198.51.100.6'])->post('/learn/login',['email'=>'limited@example.test','password'=>'wrong'])->assertStatus(429);
    }
}
