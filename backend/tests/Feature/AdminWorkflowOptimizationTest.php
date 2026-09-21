<?php

namespace Tests\Feature;

use App\Jobs\ExtractPdfPage;
use App\Models\LearningContentImport;
use App\Models\PdfExtractionRun;
use App\Models\PdfQuestionImport;
use App\Models\Question;
use App\Models\User;
use App\Services\NepaliQuestionOcr;
use App\Services\PdfQuestionExtractor;
use App\Services\SimilarQuestions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminWorkflowOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private function source(): LearningContentImport
    {
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        Storage::fake('learning-content');
        Storage::disk('learning-content')->put('bank.pdf', '%PDF-test');
        $source = LearningContentImport::create(['source_key' => 'kalanki', 'source_name' => 'Bank', 'source_url' => 'https://tmokalanki.bagamati.gov.np/', 'asset_url' => 'https://giwmscdnone.gov.np/bank.pdf', 'url_hash' => hash('sha256', 'bank'), 'kind' => 'question-bank-document', 'title' => 'Bank', 'fetched_at' => now()]);
        $source->forceFill(['file_path' => 'bank.pdf', 'file_hash' => hash('sha256', '%PDF-test')])->save();

        return $source;
    }

    private function candidate($source, int $number): PdfQuestionImport
    {
        return PdfQuestionImport::create(['learning_content_import_id' => $source->id, 'source_hash' => $source->file_hash, 'fingerprint' => hash('sha256', (string) $number), 'page' => 3, 'number' => $number, 'payload' => ['question' => 'Question '.$number, 'correctOption' => '', 'option1' => 'One', 'option2' => 'Two', 'option3' => 'Three', 'option4' => 'Four', 'category' => 'General', 'difficulty' => 'Easy'], 'warnings' => [], 'raw_text' => 'Raw']);
    }

    public function test_review_navigation_and_save_next_preserve_filters(): void
    {
        $source = $this->source();
        $first = $this->candidate($source, 1);
        $middle = $this->candidate($source, 2);
        $last = $this->candidate($source, 3);
        $middle->update(['status' => 'Rejected']);
        $filters = ['status' => 'Pending', 'needs' => 'answer', 'page' => 2];
        $this->get(route('pdfQuestions.show', ['candidate' => $first] + $filters))->assertOk()->assertViewHas('next', fn ($next) => $next->id === $last->id)->assertViewHas('previous', null);
        $this->post(route('pdfQuestions.review', ['candidate' => $first] + $filters), ['decision' => 'Rejected', 'next' => 1])->assertRedirect(route('pdfQuestions.show', ['candidate' => $last] + $filters));
        $this->post(route('pdfQuestions.review', ['candidate' => $last] + $filters), ['decision' => 'Rejected', 'next' => 1])->assertRedirect(route('pdfQuestions', ['resource' => $source] + $filters));
    }

    public function test_background_run_is_queued_and_processes_pages_once(): void
    {
        Queue::fake();
        $source = $this->source();
        $this->post(route('pdfRuns.store', $source), ['mode' => 'text', 'from_page' => 1, 'to_page' => 100])->assertRedirect();
        $run = PdfExtractionRun::firstOrFail();
        $this->assertSame('Queued', $run->status);
        $this->assertSame(auth()->id(), $run->requested_by);
        Queue::assertPushed(ExtractPdfPage::class, fn ($job) => $job->connection === 'pdf-extraction');
        $this->post(route('pdfRuns.store', $source), ['mode' => 'text', 'from_page' => 1, 'to_page' => 2])->assertStatus(409);
        $extractor = $this->mock(PdfQuestionExtractor::class);
        $extractor->shouldReceive('extract')->twice()->andReturn(['added' => 2, 'known' => 0, 'pages' => 2]);
        $job = new ExtractPdfPage($run->id, 1, 1);
        $job->handle($extractor, app(NepaliQuestionOcr::class));
        $job->handle($extractor, app(NepaliQuestionOcr::class)); // stale delivery
        $this->assertSame(2, $run->fresh()->next_page);
        (new ExtractPdfPage($run->id, 1, 2))->handle($extractor, app(NepaliQuestionOcr::class));
        $this->assertSame('Completed', $run->fresh()->status);
        $this->assertSame(4, $run->fresh()->added);
        $this->get(route('pdfRuns.status', $run))->assertOk()->assertJsonPath('status', 'Completed');
        $this->get(route('pdfRuns.show', $run))->assertOk();
    }

    public function test_run_cancel_failure_retry_and_permissions(): void
    {
        Queue::fake();
        $source = $this->source();
        $this->post(route('pdfRuns.store', $source), ['mode' => 'ocr', 'from_page' => 5, 'to_page' => 6]);
        $run = PdfExtractionRun::firstOrFail();
        $job = new ExtractPdfPage($run->id, 1, 5);
        $job->failed(new \RuntimeException('private internal error'));
        $this->assertSame('Failed', $run->fresh()->status);
        $this->post(route('pdfRuns.retry', $run))->assertRedirect();
        $this->assertSame(2, $run->fresh()->generation);
        $job->failed(new \RuntimeException);
        $this->assertSame('Queued', $run->fresh()->status);
        $this->post(route('pdfRuns.cancel', $run))->assertRedirect();
        $this->assertSame('Cancelled', $run->fresh()->status);
        $ocr = $this->mock(NepaliQuestionOcr::class);
        $ocr->shouldNotReceive('extract');
        (new ExtractPdfPage($run->id, 2, 5))->handle(app(PdfQuestionExtractor::class), $ocr);
        $this->actingAs(User::factory()->create(['role' => 'User']));
        $this->get(route('pdfRuns.show', $run))->assertForbidden();
        $this->get(route('pdfRuns.status', $run))->assertForbidden();
        $this->post(route('pdfRuns.cancel', $run))->assertForbidden();
        $this->post(route('pdfRuns.retry', $run))->assertForbidden();
        $this->post(route('pdfRuns.store', $source), [])->assertForbidden();
    }

    public function test_similarity_flags_candidates_and_normalized_exact_matches_block_import(): void
    {
        $source = $this->source();
        $candidate = $this->candidate($source, 1);
        $candidate->update(['payload' => array_replace($candidate->payload, ['question' => 'What should drivers do before crossing this road?'])]);
        $other = $this->candidate($source, 2);
        $other->update(['payload' => array_replace($other->payload, ['question' => 'What should drivers do before crossing a road?'])]);
        $matches = app(SimilarQuestions::class)->find($candidate);
        $this->assertSame($other->id, $matches[0]['id']);
        $this->assertFalse($matches[0]['exact']);
        $this->get(route('pdfQuestions.show', $candidate))->assertSee('Possible Duplicates');
        $question = Question::create(array_replace($candidate->payload, ['question' => 'WHAT should drivers do before crossing this road!!', 'correctOption' => 'A', 'status' => 'Draft']));
        $question->delete();
        $this->post(route('pdfQuestions.review', $candidate), array_replace($candidate->payload, ['decision' => 'Imported', 'confirmed' => 1, 'correctOption' => 'A']))->assertSessionHasErrors('question');
        $this->assertSame('Pending', $candidate->fresh()->status);
    }

    public function test_dashboard_counts_and_review_index_follow_edits(): void
    {
        $source = $this->source();
        $candidate = $this->candidate($source, 1);
        $candidate->update(['payload' => $candidate->payload + ['_extraction_method' => 'ocr'], 'warnings' => ['May require a diagram.']]);
        $this->get(route('adminDashboard'))->assertOk()->assertViewHas('reviewWorkload', fn ($counts) => (int) $counts->pending === 1 && (int) $counts->answers === 1 && (int) $counts->ocr === 1 && (int) $counts->diagrams === 1);
        $candidate->update(['payload' => array_replace($candidate->payload, ['correctOption' => 'A']), 'status' => 'Imported']);
        $this->assertSame(0, (int) $candidate->fresh()->needs_answer);
        $this->get(route('adminDashboard'))->assertViewHas('reviewWorkload', fn ($counts) => (int) $counts->pending === 0);
        $this->assertNotEmpty($candidate->fresh()->question_hash);
    }

    public function test_running_cancellation_finishes_one_page_without_dispatching_another(): void
    {
        Queue::fake();
        $source = $this->source();
        $run = PdfExtractionRun::create(['learning_content_import_id' => $source->id, 'source_hash' => $source->file_hash, 'mode' => 'text', 'from_page' => 1, 'to_page' => 2, 'next_page' => 1]);
        $text = $this->mock(PdfQuestionExtractor::class);
        $text->shouldReceive('extract')->once()->andReturnUsing(function () use ($run) {
            $run->update(['cancel_requested' => true]);

            return ['added' => 1, 'known' => 0, 'pages' => 2];
        });
        (new ExtractPdfPage($run->id,1,1))->handle($text,app(NepaliQuestionOcr::class));
        $this->assertSame('Cancelled',$run->fresh()->status);
        $this->assertSame(2,$run->fresh()->next_page);
        Queue::assertNothingPushed();
    }
}
