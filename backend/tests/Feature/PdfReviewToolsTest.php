<?php

namespace Tests\Feature;

use App\Models\LearningContentImport;
use App\Models\PdfQuestionImport;
use App\Models\User;
use App\Services\LocalPdfTools;
use App\Services\NepaliQuestionOcr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfReviewToolsTest extends TestCase
{
    use RefreshDatabase;

    private function source(): LearningContentImport
    {
        Storage::fake('learning-content');
        Storage::disk('learning-content')->put('bank.pdf', '%PDF-test');
        $source = LearningContentImport::create(['source_key' => 'kalanki', 'source_name' => 'Official bank', 'source_url' => 'https://tmokalanki.bagamati.gov.np/', 'asset_url' => 'https://giwmscdnone.gov.np/nep.pdf', 'url_hash' => hash('sha256', 'nep'), 'kind' => 'question-bank-document', 'title' => 'Nepali question bank', 'fetched_at' => now()]);
        $source->forceFill(['file_path' => 'bank.pdf', 'file_hash' => hash('sha256', '%PDF-test')])->save();

        return $source;
    }

    private function candidate(LearningContentImport $source, int $number = 1, array $payload = [], array $warnings = []): PdfQuestionImport
    {
        return PdfQuestionImport::create(['learning_content_import_id' => $source->id, 'source_hash' => $source->file_hash, 'fingerprint' => hash('sha256', 'candidate'.$number), 'page' => 5, 'number' => $number, 'payload' => $payload + ['question' => 'Question '.$number, 'option1' => 'One', 'option2' => 'Two', 'option3' => 'Three', 'option4' => 'Four', 'correctOption' => '', 'category' => 'General', 'difficulty' => 'Medium', 'explanation' => ''], 'raw_text' => 'Original text', 'warnings' => $warnings]);
    }

    private function admin(): void
    {
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
    }

    public function test_filters_combine_with_status_and_preserve_query_strings(): void
    {
        $this->admin();
        $source = $this->source();
        $this->candidate($source, 1, ['correctOption' => 'A']);
        $this->candidate($source, 2, ['_extraction_method' => 'ocr'], ['May require a diagram.']);
        $this->candidate($source, 3, [], ['Options are incomplete.'])->update(['status' => 'Rejected']);
        $this->get(route('pdfQuestions', ['resource' => $source, 'needs' => 'answer', 'status' => 'Pending']))->assertOk()->assertViewHas('items', fn ($items) => $items->total() === 1 && str_contains($items->url(2), 'needs=answer'));
        foreach (['diagram' => 1, 'options' => 1, 'ocr' => 1] as $needs => $total) {
            $this->get(route('pdfQuestions', ['resource' => $source, 'needs' => $needs]))->assertOk()->assertViewHas('items', fn ($items) => $items->total() === $total);
        }
        $this->get(route('pdfQuestions', ['resource' => $source, 'needs' => 'invalid']))->assertSessionHasErrors('needs');
    }

    public function test_page_preview_is_private_cached_and_source_checked(): void
    {
        $this->admin();
        $source = $this->source();
        $candidate = $this->candidate($source);
        $tools = $this->partialMock(LocalPdfTools::class);
        $tools->shouldReceive('run')->once()->with('render', \Mockery::type('array'))->andReturnUsing(function ($mode, $args) {
            file_put_contents($args[array_search('--output', $args) + 1], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a5XcAAAAASUVORK5CYII='));

            return ['pages' => 70, 'page' => 5];
        });
        $response = $this->get(route('pdfQuestions.page', $candidate));
        $response->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->get(route('pdfQuestions.page', $candidate))->assertOk();
        $this->get(route('pdfQuestions.show', $candidate))->assertOk()->assertSee('Source PDF page preview')->assertSee('preview-zoom');
        $this->get(route('pdfQuestions.page', ['candidate' => $candidate, 'page' => -1]))->assertSessionHasErrors('page');
        Storage::disk('learning-content')->put('bank.pdf', 'tampered');
        $this->get(route('pdfQuestions.page', $candidate))->assertStatus(422);
        $source->forceFill(['file_hash' => hash('sha256', 'new')])->save();
        $this->get(route('pdfQuestions.page', $candidate))->assertStatus(409);
    }

    public function test_nepali_rows_are_parsed_without_guessing_answers_or_unknown_labels(): void
    {
        $ocr = app(NepaliQuestionOcr::class);
        $row = ['row' => 3, 'number' => '१२.', 'text' => "यो चिन्ह के हो?\n(क) एक (ख) दुई (ग) तीन (घ) चार", 'confidence' => 90];
        $candidate = $ocr->parseRow($row);
        $this->assertSame(12, $candidate['number']);
        $this->assertSame('दुई', $candidate['payload']['option2']);
        $this->assertSame('', $candidate['payload']['correctOption']);
        $this->assertSame('ocr', $candidate['payload']['_extraction_method']);
        $this->assertStringContainsString('diagram', implode(' ', $candidate['warnings']));
        $row['number'] = '?';
        $row['confidence'] = 50;
        $row['text'] = 'प्रश्न? (क) एक (ख) दुई (ग) तीन (ङ) चार';
        $candidate = $ocr->parseRow($row);
        $this->assertSame(3, $candidate['number']);
        $this->assertSame('', $candidate['payload']['option4']);
        $this->assertStringContainsString('Low OCR confidence', implode(' ', $candidate['warnings']));
        $this->assertStringContainsString('Options are incomplete', implode(' ', $candidate['warnings']));
        $this->assertNull($ocr->parseRow(['row' => 1, 'number' => '', 'text' => 'प्रश्न', 'confidence' => 90]));
    }

    public function test_ocr_adds_only_pending_candidates_and_preserves_reviews_and_provenance(): void
    {
        $this->admin();
        $source = $this->source();
        $this->mock(LocalPdfTools::class)->shouldReceive('ocr')->twice()->andReturn(['pages' => 70, 'rows' => [['row' => 3, 'number' => '१.', 'text' => 'प्रश्न के हो? (क) एक (ख) दुई (ग) तीन (घ) चार', 'confidence' => 89]]]);
        $this->post(route('pdfQuestions.ocr', $source), ['ocr_page' => 5])->assertSessionHasNoErrors();
        $candidate = PdfQuestionImport::firstOrFail();
        $this->assertSame('Pending', $candidate->status);
        $this->assertSame('', $candidate->payload['correctOption']);
        $this->assertDatabaseCount('questions', 0);
        $data = $candidate->payload + ['decision' => 'Imported', 'confirmed' => 1];
        $data['correctOption'] = 'B';
        $this->post(route('pdfQuestions.review', $candidate), $data)->assertSessionHasNoErrors();
        $this->assertSame('ocr', $candidate->fresh()->payload['_extraction_method']);
        $this->post(route('pdfQuestions.ocr', $source), ['ocr_page' => 5])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('pdf_question_imports', 1);
        $this->assertSame('Imported', $candidate->fresh()->status);
        $this->assertDatabaseHas('questions', ['status' => 'Draft']);
    }

    public function test_ocr_failure_and_rejected_sources_do_not_add_candidates(): void
    {
        $this->admin();
        $source = $this->source();
        $this->partialMock(LocalPdfTools::class)->shouldReceive('run')->never();
        $source->forceFill(['status' => 'Rejected'])->save();
        $this->post(route('pdfQuestions.ocr', $source), ['ocr_page' => 5])->assertSessionHasErrors('ocr');
        $this->post(route('pdfQuestions.ocr', $source), ['ocr_page' => 0])->assertSessionHasErrors('ocr_page');
        $this->assertDatabaseCount('pdf_question_imports', 0);
        $this->artisan('content:ocr-questions', ['resource' => $source->id, '--from' => 5, '--to' => 4])->assertFailed();
    }

    public function test_ocr_and_preview_require_admin_access(): void
    {
        $source = $this->source();
        $candidate = $this->candidate($source);
        $this->get(route('pdfQuestions.page', $candidate))->assertRedirect(route('login'));
        $this->post(route('pdfQuestions.ocr', $source), ['ocr_page' => 5])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'User']));
        $this->get(route('pdfQuestions.page', $candidate))->assertForbidden();
        $this->post(route('pdfQuestions.ocr', $source), ['ocr_page' => 5])->assertForbidden();
    }
}
