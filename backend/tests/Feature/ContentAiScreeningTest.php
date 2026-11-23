<?php
namespace Tests\Feature;
use App\Models\{LearningContentImport,User};
use App\Jobs\ScreenImportedContent;
use App\Services\ContentAiScreening;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Queue,Http,Storage};
use Tests\TestCase;
class ContentAiScreeningTest extends TestCase {
    use RefreshDatabase;
    public function test_notice_page_usage_fields_do_not_affect_provider_accounting(): void {
        config(['pdf-translation.gemini_key'=>'fake','pdf-translation.gemini_model'=>'gemini-test','content-screening.daily_limit'=>100]);
        $record=\App\Models\GovernmentNoticeImport::create(['source_name'=>'Official','source_domain'=>'dotm.gov.np','source_url'=>'https://dotm.gov.np/notice','title'=>'Notice','content_hash'=>hash('sha256','notice'),'status'=>'Pending','fetched_at'=>now()]);
        $page=new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response(200,[],'<html><head><style>.nav { color: red; }</style></head><body><header>Site menu</header><main><h1>Notice</h1><p>Driving licence notice</p></main></body></html>'));
        $this->mock(\App\Services\RedirectFollowingRequest::class)->shouldReceive('get')->once()->andReturn($page);
        Http::fake(['*'=>Http::response(['usageMetadata'=>['totalTokenCount'=>23],'candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>json_encode(['verdict'=>'Ready','summary'=>'Review dates','issues'=>[]])]]]]]])]);
        app(ContentAiScreening::class)->screen($record);
        $this->assertSame('Ready',$record->fresh()->ai_status);
        $this->assertSame(23,\Illuminate\Support\Facades\DB::table('content_ai_usage')->sum('tokens'));
    }
    public function test_screening_uses_flash_lite_while_chat_and_translation_stay_on_flash(): void {
        config(['pdf-translation.gemini_key'=>'fake','pdf-translation.gemini_model'=>'gemini-3.5-flash','premium.chat_model'=>'gemini-3.5-flash','content-screening.daily_limit'=>100]);
        // The screening model is deliberately not set here: it must default to Flash-Lite.
        $this->assertSame('gemini-3.5-flash-lite', config('content-screening.gemini_model'));
        $record=\App\Models\GovernmentNoticeImport::create(['source_name'=>'Official','source_domain'=>'dotm.gov.np','source_url'=>'https://dotm.gov.np/notice','title'=>'Notice','content_hash'=>hash('sha256','notice-flash-lite'),'status'=>'Pending','fetched_at'=>now()]);
        $page=new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response(200,[],'<html><body><main><h1>Notice</h1><p>Driving licence notice</p></main></body></html>'));
        $this->mock(\App\Services\RedirectFollowingRequest::class)->shouldReceive('get')->once()->andReturn($page);
        Http::fake(['*'=>Http::response(['usageMetadata'=>['totalTokenCount'=>11],'candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>json_encode(['verdict'=>'Ready','summary'=>'Review dates','issues'=>[]])]]]]]])]);
        app(ContentAiScreening::class)->screen($record);
        $this->assertSame('Ready',$record->fresh()->ai_status);
        $recorded=Http::recorded();
        $this->assertNotEmpty($recorded);
        $this->assertStringContainsString('models/gemini-3.5-flash-lite:generateContent', $recorded[0][0]->url());
        // Chat and translation still point at Flash; only screening moved.
        $this->assertSame('gemini-3.5-flash', config('pdf-translation.gemini_model'));
        $this->assertSame('gemini-3.5-flash', config('premium.chat_model'));
    }
    public function test_notice_screening_refuses_cross_host_redirect(): void {
        config(['pdf-translation.gemini_key'=>'fake','pdf-translation.gemini_model'=>'gemini-test','content-screening.daily_limit'=>100]);
        $record=\App\Models\GovernmentNoticeImport::create(['source_name'=>'Official','source_domain'=>'dotm.gov.np','source_url'=>'https://dotm.gov.np/notice','title'=>'Notice','content_hash'=>hash('sha256','notice-redirect'),'status'=>'Pending','fetched_at'=>now()]);
        $this->mock(\App\Services\RedirectFollowingRequest::class)->shouldReceive('get')->once()->andThrow(new \RuntimeException('The redirect target is not on the official allowlist.'));
        app(ContentAiScreening::class)->screen($record);
        $fresh=$record->fresh();
        $this->assertSame('Failed',$fresh->ai_status);
        $this->assertSame('Pending',$fresh->status);
        $this->assertStringContainsString('not on the official allowlist',$fresh->ai_report);
    }
    public function test_new_import_is_screened_but_never_automatically_approved(): void {
        Queue::fake(); Storage::fake('learning-content');
        config(['content-screening.enabled'=>true,'pdf-translation.gemini_key'=>'fake','pdf-translation.gemini_model'=>'gemini-test']);
        $record=LearningContentImport::create(['source_key'=>'kalanki','source_name'=>'Official','source_url'=>'https://example.org','asset_url'=>'https://example.org/file.pdf','url_hash'=>hash('sha256','test'),'title'=>'Test','kind'=>'question-bank-document','fetched_at'=>now()]);
        Queue::assertPushed(ScreenImportedContent::class);
        $this->assertSame('Queued',$record->ai_status);
        $bytes="%PDF-1.4\nTest\n%%EOF";
        Storage::disk('learning-content')->put('file.pdf',$bytes);
        $record->forceFill(['file_path'=>'file.pdf','file_hash'=>hash('sha256',$bytes)])->save();
        Http::fake(['*'=>Http::response(['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>json_encode(['verdict'=>'Flagged','summary'=>'Check edition','issues'=>['Old date']])]]]]]])]);
        app(ContentAiScreening::class)->screen($record);
        $this->assertSame('Flagged',$record->fresh()->ai_status);
        $this->assertSame('Pending',$record->fresh()->status);
        app(ContentAiScreening::class)->requireScreened($record->fresh());
        $record->forceFill(['title'=>'Changed'])->save();
        try { app(ContentAiScreening::class)->requireScreened($record); $this->fail('Stale screening accepted'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(409,$e->getStatusCode()); }
        config(['pdf-translation.gemini_key'=>null]);
        app(ContentAiScreening::class)->screen($record);
        $this->assertSame('Failed',$record->fresh()->ai_status);
        $this->assertSame('Pending',$record->fresh()->status);
    }
    public function test_missing_stored_file_is_re_downloaded_instead_of_failing(): void {
        Queue::fake(); Storage::fake('learning-content');
        config(['content-screening.enabled'=>true,'pdf-translation.gemini_key'=>'fake','pdf-translation.gemini_model'=>'gemini-test','content-screening.daily_limit'=>100]);
        $bytes="%PDF-1.4\nRefetched\n%%EOF";
        $download=$this->mock(\App\Services\LearningContentDownload::class);
        $download->shouldReceive('fetch')->once()->andReturnUsing(function ($record) use ($bytes) {
            Storage::disk('learning-content')->put('refetched.pdf',$bytes);
            return ['file_path'=>'refetched.pdf','mime_type'=>'application/pdf','file_size'=>strlen($bytes),'file_hash'=>hash('sha256',$bytes),'downloaded_at'=>now()];
        });
        $record=LearningContentImport::create(['source_key'=>'kalanki','source_name'=>'Official','source_url'=>'https://example.org','asset_url'=>'https://example.org/file.pdf','url_hash'=>hash('sha256','refetch'),'title'=>'Test','kind'=>'question-bank-document','fetched_at'=>now()]);
        $record->forceFill(['file_path'=>'stale.pdf','file_hash'=>hash('sha256','old-bytes')])->save();
        Http::fake(['*'=>Http::response(['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>json_encode(['verdict'=>'Ready','summary'=>'Fresh copy','issues'=>[]])]]]]]])]);
        app(ContentAiScreening::class)->screen($record);
        $fresh=$record->fresh();
        $this->assertSame('Ready',$fresh->ai_status);
        $this->assertSame('refetched.pdf',$fresh->file_path);
        $this->assertSame(hash('sha256',$bytes),$fresh->file_hash);
        $this->assertSame('Pending',$fresh->status);
    }
    public function test_transient_gemini_503_requeues_with_backoff_instead_of_failing(): void {
        Queue::fake();
        config(['pdf-translation.gemini_key'=>'fake','pdf-translation.gemini_model'=>'gemini-test','content-screening.daily_limit'=>100]);
        $record=\App\Models\GovernmentNoticeImport::create(['source_name'=>'Official','source_domain'=>'dotm.gov.np','source_url'=>'https://dotm.gov.np/notice-503','title'=>'Notice','content_hash'=>hash('sha256','notice-503'),'status'=>'Pending','fetched_at'=>now()]);
        $page=new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response(200,[],'<html><body><main><h1>Notice</h1><p>Driving licence notice</p></main></body></html>'));
        $this->mock(\App\Services\RedirectFollowingRequest::class)->shouldReceive('get')->once()->andReturn($page);
        Http::fake(['*'=>Http::response(['error'=>['message'=>'This model is currently experiencing high demand. Spikes in demand are usually temporary. Please try again later.']],503)]);
        app(ContentAiScreening::class)->screen($record);
        $fresh=$record->fresh();
        $this->assertSame('Queued',$fresh->ai_status);
        $this->assertSame(1,$fresh->ai_retry_count);
        $this->assertStringContainsString('retry automatically in 2 minute',$fresh->ai_report);
        Queue::assertPushed(ScreenImportedContent::class, function ($job) { return $job->delay === 120; });
    }
    public function test_gemini_timeout_requeues_instead_of_failing(): void {
        Queue::fake();
        config(['pdf-translation.gemini_key'=>'fake','pdf-translation.gemini_model'=>'gemini-test','content-screening.daily_limit'=>100]);
        $record=\App\Models\GovernmentNoticeImport::create(['source_name'=>'Official','source_domain'=>'dotm.gov.np','source_url'=>'https://dotm.gov.np/notice-timeout','title'=>'Notice','content_hash'=>hash('sha256','notice-timeout'),'status'=>'Pending','fetched_at'=>now()]);
        $page=new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response(200,[],'<html><body><main><h1>Notice</h1><p>Driving licence notice</p></main></body></html>'));
        $this->mock(\App\Services\RedirectFollowingRequest::class)->shouldReceive('get')->once()->andReturn($page);
        Http::fake(['*' => function () {
            throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out after 90002 milliseconds with 0 bytes received (see https://curl.se/libcurl/c/libcurl-errors.html) for https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent');
        }]);
        app(ContentAiScreening::class)->screen($record);
        $fresh=$record->fresh();
        $this->assertSame('Queued',$fresh->ai_status);
        $this->assertSame(1,$fresh->ai_retry_count);
        Queue::assertPushed(ScreenImportedContent::class, function ($job) { return $job->delay === 120; });
    }
    public function test_transient_error_fails_when_retry_budget_is_exhausted(): void {
        Queue::fake();
        config(['pdf-translation.gemini_key'=>'fake','pdf-translation.gemini_model'=>'gemini-test','content-screening.daily_limit'=>100]);
        $record=\App\Models\GovernmentNoticeImport::create(['source_name'=>'Official','source_domain'=>'dotm.gov.np','source_url'=>'https://dotm.gov.np/notice-budget','title'=>'Notice','content_hash'=>hash('sha256','notice-budget'),'status'=>'Pending','fetched_at'=>now()]);
        $record->forceFill(['ai_retry_count'=>3])->save();
        $page=new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response(200,[],'<html><body><main><h1>Notice</h1><p>Driving licence notice</p></main></body></html>'));
        $this->mock(\App\Services\RedirectFollowingRequest::class)->shouldReceive('get')->once()->andReturn($page);
        Http::fake(['*'=>Http::response(['error'=>['message'=>'Quota exceeded.']],429)]);
        app(ContentAiScreening::class)->screen($record);
        $fresh=$record->fresh();
        $this->assertSame('Failed',$fresh->ai_status);
        $this->assertSame(3,$fresh->ai_retry_count);
        // No delayed retry was queued: the only pushes are the observer's
        // undelayed dispatch on create.
        $this->assertCount(0, Queue::pushed(ScreenImportedContent::class)->filter(fn ($job) => $job->delay !== null));
    }
    public function test_permanent_page_fetch_failure_still_fails_immediately(): void {
        Queue::fake();
        config(['pdf-translation.gemini_key'=>'fake','pdf-translation.gemini_model'=>'gemini-test','content-screening.daily_limit'=>100]);
        $record=\App\Models\GovernmentNoticeImport::create(['source_name'=>'Official','source_domain'=>'dotm.gov.np','source_url'=>'https://dotm.gov.np/notice-404','title'=>'Notice','content_hash'=>hash('sha256','notice-404'),'status'=>'Pending','fetched_at'=>now()]);
        $this->mock(\App\Services\RedirectFollowingRequest::class)->shouldReceive('get')->once()->andReturn(new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response(404,[],'Not found')));
        app(ContentAiScreening::class)->screen($record);
        $fresh=$record->fresh();
        $this->assertSame('Failed',$fresh->ai_status);
        $this->assertSame(0,$fresh->ai_retry_count);
        $this->assertStringContainsString('HTTP 404',$fresh->ai_report);
    }
}
