<?php
namespace Tests\Feature;
use App\Models\LearningContentImport;
use App\Models\PdfTranslation;
use App\Models\User;
use App\Services\LocalPdfTools;
use App\Services\PdfTextTranslator;
use App\Services\ReviewedTranslationPdf;
use App\Jobs\TranslatePdfPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class PdfTranslationTest extends TestCase {
    use RefreshDatabase;
    protected function setUp(): void {
        parent::setUp();
        config(['pdf-translation.driver'=>'google', 'pdf-translation.google_key'=>null,
            'pdf-translation.gemini_key'=>null, 'pdf-translation.local_model'=>null]);
        Http::preventStrayRequests();
    }
    public function test_gemini_translates_both_directions_and_excludes_thought_parts(): void {
        config(['pdf-translation.driver'=>'gemini','pdf-translation.gemini_key'=>'test-gemini-key']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['thought'=>true,'text'=>'Hidden reasoning'],['text'=>'रोक्नुहोस्']]]]]])
            ->push(['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>'Stop &amp; wait']]]]]])]);
        $translator = app(PdfTextTranslator::class);
        $this->assertTrue($translator->ready());
        $this->assertSame('रोक्नुहोस्',$translator->translate('Stop','en','ne'));
        $this->assertSame('Stop &amp; wait',$translator->translate('रोक्नुहोस्','ne','en'));
        Http::assertSent(fn($r)=>$r->hasHeader('x-goog-api-key','test-gemini-key')
            && $r['contents'][0]['parts'][0]['text']==='Stop'
            && str_contains($r['systemInstruction']['parts'][0]['text'],'English to Nepali')
            && !str_contains($r->url(),'test-gemini-key'));
        Http::assertSent(fn($r)=>str_contains($r['systemInstruction']['parts'][0]['text'],'Nepali to English'));
    }
    public function test_gemini_rejects_truncated_or_blocked_results(): void {
        config(['pdf-translation.driver'=>'gemini','pdf-translation.gemini_key'=>'secret']);
        Http::fake(['*'=>Http::sequence()
            ->push(['candidates'=>[['finishReason'=>'MAX_TOKENS','content'=>['parts'=>[['text'=>'Partial translation']]]]]])
            ->push(['promptFeedback'=>['blockReason'=>'SAFETY']])
            ->push(['error'=>['message'=>'private provider detail']],429)]);
        for ($i=0; $i<3; $i++) {
            try { app(PdfTextTranslator::class)->translate('Stop','en','ne'); $this->fail('Expected translation failure.'); }
            catch (\RuntimeException $e) { $this->assertSame('Translation failed. Check provider configuration and retry.',$e->getMessage()); }
        }
    }
    public function test_translation_is_private_requires_review_and_generates_missing_language(): void {
        Storage::fake('learning-content'); Queue::fake();
        $this->actingAs(User::factory()->create(['role'=>'Admin']));
        $pdf = \Illuminate\Http\UploadedFile::fake()->createWithContent('bank.pdf', "%PDF-1.4\n%%EOF");
        $this->post(route('pdfTranslations.upload'), ['title'=>'Bank','pdf'=>$pdf])->assertRedirect();
        $source = LearningContentImport::firstOrFail();
        $this->assertSame('Pending', $source->status);
        $this->post(route('pdfTranslations.start', $source), ['source_language'=>'en','confirmed'=>1])->assertStatus(422);
        config(['pdf-translation.google_key'=>'test-key']);
        $this->post(route('pdfTranslations.start', $source), ['source_language'=>'en','confirmed'=>1])->assertRedirect();
        Queue::assertPushed(TranslatePdfPage::class);
        $run = PdfTranslation::firstOrFail();
        $this->get(route('pdfTranslations.show',$run))->assertOk();
        $tools = $this->mock(LocalPdfTools::class);
        $tools->shouldReceive('sourcePath')->andReturn('/private/bank.pdf');
        $tools->shouldReceive('run')->once()->andReturn(['pages'=>1,'text'=>'Stop']);
        Http::fake(['translation.googleapis.com/*'=>Http::response(['data'=>['translations'=>[['translatedText'=>'रोक्नुहोस्']]]])]);
        (new TranslatePdfPage($run->id,1))->handle($tools, app(PdfTextTranslator::class));
        $run->refresh();
        $this->assertSame('Review',$run->status);
        Http::assertSent(fn($r)=>$r['source']==='en' && $r['target']==='ne' && $r['q']==='Stop');
        $this->post(route('pdfTranslations.finish',$run),['confirmed'=>1])->assertStatus(409);
        $page = $run->pages()->firstOrFail();
        $this->post(route('pdfTranslations.page',$run), ['page'=>1,'translated_text'=>'रोक्नुहोस्','confirmed'=>1,'version'=>str_repeat('0',64)])->assertStatus(409);
        $this->post(route('pdfTranslations.page',$run), ['page'=>1,'translated_text'=>'रोक्नुहोस्','confirmed'=>1,'version'=>hash('sha256',$page->translated_text)])->assertRedirect();
        $this->post(route('pdfTranslations.finish',$run),['confirmed'=>1])->assertStatus(409);
        $source->forceFill(['status'=>'Approved','language'=>'English','licence_category'=>'B','edition'=>'2078'])->save();
        $this->mock(ReviewedTranslationPdf::class)->shouldReceive('generate')->once()->andReturn(['file_path'=>'translated.pdf','file_hash'=>str_repeat('a',64),'file_size'=>200,'mime_type'=>'application/pdf','downloaded_at'=>now()]);
        $this->post(route('pdfTranslations.finish',$run),['confirmed'=>1])->assertRedirect();
        $output = LearningContentImport::findOrFail($run->fresh()->output_resource_id);
        $this->assertSame('Nepali',$output->language);
        $this->assertSame('Approved',$output->status);
        $this->assertDatabaseCount('exam_papers',0);
        $this->post(route('pdfTranslations.finish',$run),['confirmed'=>1])->assertStatus(409);
        $this->actingAs(User::factory()->create(['role'=>'User']))->get(route('pdfTranslations.show',$run))->assertForbidden();
    }
    public function test_reverse_translation_and_failure_without_leaking_provider_response(): void {
        config(['pdf-translation.google_key'=>'secret']);
        Http::fake(['*'=>Http::sequence()->push(['data'=>['translations'=>[['translatedText'=>'Stop']]]])->push(['error'=>'private provider detail'],403)]);
        $this->assertSame('Stop',app(PdfTextTranslator::class)->translate('रोक्नुहोस्','ne','en'));
        Http::assertSent(fn($r)=>$r['source']==='ne' && $r['target']==='en');
        $this->expectExceptionMessage('Translation failed. Check provider configuration and retry.');
        app(PdfTextTranslator::class)->translate('रोक्नुहोस्','ne','en');
    }
}
