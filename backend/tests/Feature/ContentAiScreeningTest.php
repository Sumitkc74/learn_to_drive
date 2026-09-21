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
        $record=\App\Models\GovernmentNoticeImport::create(['source_name'=>'Official','source_domain'=>'example.org','source_url'=>'https://example.org/notice','title'=>'Notice','content_hash'=>hash('sha256','notice'),'status'=>'Pending','fetched_at'=>now()]);
        $page=new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response(200,[],json_encode(['usageMetadata'=>['totalTokenCount'=>999999],'text'=>'Driving licence notice'])));
        $this->mock(\App\Services\PublicWebsiteRequest::class)->shouldReceive('get')->once()->andReturn($page);
        Http::fake(['*'=>Http::response(['usageMetadata'=>['totalTokenCount'=>23],'candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>json_encode(['verdict'=>'Ready','summary'=>'Review dates','issues'=>[]])]]]]]])]);
        app(ContentAiScreening::class)->screen($record);
        $this->assertSame('Ready',$record->fresh()->ai_status);
        $this->assertSame(23,\Illuminate\Support\Facades\DB::table('content_ai_usage')->sum('tokens'));
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
}
