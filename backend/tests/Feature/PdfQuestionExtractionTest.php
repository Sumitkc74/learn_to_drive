<?php

namespace Tests\Feature;

use App\Models\LearningContentImport;
use App\Models\PdfQuestionImport;
use App\Models\Question;
use App\Models\User;
use App\Services\PdfQuestionExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfQuestionExtractionTest extends TestCase
{
    use RefreshDatabase;

    private function source(): LearningContentImport
    {
        Storage::fake('learning-content');
        Storage::disk('learning-content')->put('original.pdf', 'test source');
        $source = LearningContentImport::create(['source_key' => 'kalanki', 'source_name' => 'Official bank', 'source_url' => 'https://tmokalanki.bagamati.gov.np/', 'asset_url' => 'https://giwmscdnone.gov.np/example.pdf', 'url_hash' => hash('sha256', 'pdf'), 'kind' => 'question-bank-document', 'title' => 'Question bank', 'fetched_at' => now()]);
        $source->forceFill(['file_path' => 'original.pdf', 'file_hash' => hash('sha256', 'test source')])->save();

        return $source;
    }

    private function row(): array
    {
        return ['question' => 'What should a driver do?', 'option1' => 'Stop', 'option2' => 'Wait', 'option3' => 'Turn', 'option4' => 'Proceed', 'correctOption' => 'B', 'category' => 'Traffic Rules', 'difficulty' => 'Easy', 'explanation' => ''];
    }

    public function test_continuation_answer_columns_are_reused_only_until_the_layout_changes(): void
    {
        $extractor = app(PdfQuestionExtractor::class);
        $previous = [];
        $headers = [];
        foreach (['a' => 474, 'b' => 493, 'c' => 513, 'd' => 532] as $letter => $x) {
            $headers[] = [[1, 0, 0, 1, $x, 700], $letter];
        }
        $extractor->withAnswerHeaders($headers, $previous);
        $positions = [[[1, 0, 0, 1, 85, 600], '12.'], [[1, 0, 0, 1, 492, 598.5], '√']];
        $text = "12. Test question?\n(a) One (b) Two (c) Three (d) Four";
        $rows = $extractor->parsePage($text, $extractor->withAnswerHeaders($positions, $previous));
        $this->assertSame('B', $rows[0]['payload']['correctOption']);
        $shifted = $positions;
        $shifted[1][0][4] = 550;
        $this->assertSame('', $extractor->parsePage($text, $extractor->withAnswerHeaders($shifted, $previous))[0]['payload']['correctOption']);
        $extractor->withAnswerHeaders([$headers[0]], $previous);
        $this->assertSame([], $previous);
        $this->assertSame('', $extractor->parsePage($text, $extractor->withAnswerHeaders($positions, $previous))[0]['payload']['correctOption']);
    }

    private function candidate(LearningContentImport $source): PdfQuestionImport
    {
        return PdfQuestionImport::create(['learning_content_import_id' => $source->id, 'fingerprint' => hash('sha256', 'candidate'), 'source_hash' => $source->file_hash, 'page' => 3, 'number' => 1, 'payload' => $this->row(), 'raw_text' => 'Original PDF text', 'warnings' => ['Check the original.']]);
    }

    public function test_parser_handles_inline_and_multiline_questions_and_uses_tick_coordinates(): void
    {
        $extractor = app(PdfQuestionExtractor::class);
        $next = "Government of Nepal\n7\n(d) All of the above\n66. Next question\n(a) A (b) B (c) C (d) D";
        $this->assertSame('(d) All of the above', $extractor->continuation($next));
        $continued = $extractor->parsePage("65. Which option?\n(a) One (b) Two (c) Three\n".$extractor->continuation($next), []);
        $this->assertSame('All of the above', $continued[0]['payload']['option4']);
        $this->assertSame('', $extractor->continuation("Government of Nepal\n66. Next question\n(a) A (b) B (c) C (d) D"));
        $positions = [];
        foreach (['a' => 474, 'b' => 493, 'c' => 513, 'd' => 532] as $letter => $x) {
            $positions[] = [[1, 0, 0, 1, $x, 700], $letter];
        }
        $positions[] = [[1, 0, 0, 1, 85, 600], '1.'];
        $positions[] = [[1, 0, 0, 1, 492, 598.5], '√'];
        $text = "1.\nWhat should a driver do?\n(a) Stop (b) Wait\n(c) Turn (d) Proceed\n√\n2. What does this sign mean?\na) Stop (b) Go (c) Wait (d) Turn\n";
        $rows = app(PdfQuestionExtractor::class)->parsePage($text, $positions);
        $this->assertCount(2, $rows);
        $this->assertSame('B', $rows[0]['payload']['correctOption']);
        $this->assertSame('Proceed', $rows[0]['payload']['option4']);
        $this->assertSame('', $rows[1]['payload']['correctOption']);
        $this->assertStringContainsString('diagram', implode(' ', $rows[1]['warnings']));
        $positions[] = [[1, 0, 0, 1, 512, 598.5], '√'];
        $this->assertSame('', app(PdfQuestionExtractor::class)->parsePage($text, $positions)[0]['payload']['correctOption']);
    }

    public function test_pdf_extraction_is_real_and_idempotent_without_creating_questions(): void
    {
        Storage::fake('learning-content');
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        $source = $this->source();
        $stream = 'BT /F1 12 Tf 1 0 0 1 80 600 Tm (1.) Tj 1 0 0 1 120 580 Tm (What should a driver do?) Tj 1 0 0 1 120 560 Tm (\\(a\\) Stop \\(b\\) Wait \\(c\\) Turn \\(d\\) Proceed) Tj ET';
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>', '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream"];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
        Storage::disk('learning-content')->put('test.pdf', $pdf);
        $source->forceFill(['file_path' => 'test.pdf', 'file_hash' => hash('sha256', $pdf)])->save();
        $this->post(route('pdfQuestions.extract', $source), ['from_page' => 1, 'to_page' => 10])->assertSessionHasNoErrors();
        $candidate = PdfQuestionImport::firstOrFail();
        $this->assertSame('What should a driver do?', $candidate->payload['question']);
        $this->assertSame('Wait', $candidate->payload['option2']);
        $candidate->update(['status' => 'Rejected']);
        $this->post(route('pdfQuestions.extract', $source), ['from_page' => 1, 'to_page' => 10])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('pdf_question_imports', 1);
        $this->assertSame('Rejected', $candidate->fresh()->status);
        $this->assertDatabaseCount('questions', 0);
        $this->get(route('pdfQuestions', $source))->assertOk()->assertSee('What should a driver do?');
        $this->get(route('pdfQuestions.show', $candidate))->assertOk()->assertSee('Original extracted text');
        Storage::disk('learning-content')->put('test.pdf', 'changed');
        $this->post(route('pdfQuestions.extract', $source), ['from_page' => 1, 'to_page' => 10])->assertSessionHasErrors('pdf');
    }

    public function test_import_requires_review_and_always_creates_an_attributed_draft(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $this->actingAs($admin);
        $source = $this->source();
        $candidate = $this->candidate($source);
        $data = $this->row() + ['decision' => 'Imported', 'confirmed' => 1, 'status' => 'Published', 'created_by' => 999, 'reviewed_by' => 999];
        $this->post(route('pdfQuestions.review', $candidate), array_replace($data, ['confirmed' => 0]))->assertSessionHasErrors('confirmed');
        $this->post(route('pdfQuestions.review', $candidate), array_replace($data, ['correctOption' => '']))->assertSessionHasErrors('correctOption');
        Storage::disk('learning-content')->put('original.pdf', 'changed bytes');
        $this->post(route('pdfQuestions.review', $candidate), $data)->assertStatus(409);
        Storage::disk('learning-content')->put('original.pdf', 'test source');
        $this->post(route('pdfQuestions.review', $candidate), $data)->assertSessionHasNoErrors();
        $question = Question::firstOrFail();
        $this->assertSame('Draft', $question->status);
        $this->assertSame($admin->id, $question->created_by);
        $this->assertSame($admin->id, $candidate->fresh()->reviewed_by);
        $this->assertSame($question->id, $candidate->fresh()->question_id);
        $this->post(route('pdfQuestions.review', $candidate), $data)->assertStatus(409);
        $this->assertDatabaseHas('audit_logs', ['subject_type' => 'PdfQuestionImport', 'subject_id' => $candidate->id, 'event' => 'updated']);
    }

    public function test_duplicates_rejected_sources_and_invalid_page_ranges_are_blocked(): void
    {
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        $source = $this->source();
        $candidate = $this->candidate($source);
        Question::create($this->row() + ['status' => 'Draft'])->delete();
        $data = $this->row() + ['decision' => 'Imported', 'confirmed' => 1];
        $this->post(route('pdfQuestions.review', $candidate), $data)->assertSessionHasErrors('question');
        $source->forceFill(['status' => 'Rejected'])->save();
        $this->post(route('pdfQuestions.review', $candidate), $data)->assertStatus(409);
        $this->post(route('pdfQuestions.review', $candidate), ['decision' => 'Rejected'])->assertSessionHasNoErrors();
        $this->post(route('pdfQuestions.extract', $source), ['from_page' => 10, 'to_page' => 1])->assertSessionHasErrors('to_page');
        $this->post(route('pdfQuestions.extract', $source), ['from_page' => 1, 'to_page' => 101])->assertSessionHasErrors('to_page');
        $this->assertSame('Rejected', $candidate->fresh()->status);
    }

    public function test_all_pdf_endpoints_are_admin_only(): void
    {
        $source = $this->source();
        $candidate = $this->candidate($source);
        $this->get(route('pdfQuestions', $source))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'User']));
        $this->get(route('pdfQuestions', $source))->assertForbidden();
        $this->get(route('pdfQuestions.show', $candidate))->assertForbidden();
        $this->post(route('pdfQuestions.extract', $source))->assertForbidden();
        $this->post(route('pdfQuestions.review', $candidate))->assertForbidden();
    }
}
