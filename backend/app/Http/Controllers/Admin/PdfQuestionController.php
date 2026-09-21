<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningContentImport;
use App\Models\PdfExtractionRun;
use App\Models\PdfQuestionImport;
use App\Models\Question;
use App\Services\ContentReadiness;
use App\Services\LocalPdfTools;
use App\Services\NepaliQuestionOcr;
use App\Services\PdfQuestionExtractor;
use App\Services\SimilarQuestions;
use App\Support\CandidateReviewQuery;
use App\Support\QuestionReviewIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PdfQuestionController extends Controller
{
    public function page(Request $request, PdfQuestionImport $candidate, LocalPdfTools $tools)
    {
        $page = $request->validate(['page' => ['nullable', 'integer', 'min:1', 'max:1000']])['page'] ?? $candidate->page;
        abort_unless(hash_equals($candidate->source_hash, (string) $candidate->resource->file_hash), 409, 'The source PDF has changed.');
        try {
            $path = $tools->preview($candidate->resource, (int) $page);
        } catch (\Throwable $exception) {
            report($exception);
            abort(422, 'Page preview unavailable. Check the page number or download the source PDF.');
        }

        return Storage::disk('learning-content')->response($path, 'page-'.$page.'.png', [
            'Content-Type' => 'image/png', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function ocr(Request $request, LearningContentImport $resource, NepaliQuestionOcr $ocr)
    {
        $page = $request->validate(['ocr_page' => ['required', 'integer', 'min:1', 'max:1000']])['ocr_page'];
        try {
            $result = $ocr->extract($resource, (int) $page);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['ocr' => $exception instanceof \RuntimeException ? $exception->getMessage() : 'OCR could not complete. Check the local OCR setup.']);
        }
        $message = "OCR page {$page}: {$result['added']} new candidates, {$result['known']} already known, {$result['skipped']} header or unrecognized rows skipped. Check every word and supply the correct answers before importing.";
        if (! $result['added'] && ! $result['known']) {
            $message = 'No supported question rows found on this page. The OCR profile expects a ruled question table; check the page or use manual entry.';
        }

        return redirect()->route('pdfQuestions', ['resource' => $resource, 'needs' => 'ocr'])->with('success', $message);
    }

    public function index(Request $request, LearningContentImport $resource)
    {
        abort_unless($resource->kind === 'question-bank-document', 404);
        $filters = CandidateReviewQuery::filters($request);
        $query = PdfQuestionImport::where('learning_content_import_id', $resource->id);
        $summary = (clone $query)->selectRaw('COALESCE(SUM(needs_answer), 0) AS answer, COALESCE(SUM(needs_diagram), 0) AS diagram')->first();
        $counts = ['answer' => (int) $summary->answer, 'diagram' => (int) $summary->diagram];
        $items = CandidateReviewQuery::query($resource->id, $filters)->select(['id', 'learning_content_import_id', 'page', 'number', 'status', 'payload'])->orderBy('page')->orderBy('number')->orderBy('id')->paginate(20)->withQueryString();

        $runs = PdfExtractionRun::where('learning_content_import_id', $resource->id)->latest('id')->limit(5)->get();

        return view('admin.learning-content.pdf-questions', compact('resource', 'items', 'counts', 'runs'));
    }

    public function extract(Request $request, LearningContentImport $resource, PdfQuestionExtractor $extractor)
    {
        $data = $request->validate(['from_page' => ['required', 'integer', 'min:1', 'max:1000'], 'to_page' => ['required', 'integer', 'gte:from_page', 'max:1000']]);
        if ($data['to_page'] - $data['from_page'] >= 100) {
            throw ValidationException::withMessages(['to_page' => 'Extract at most 100 pages at a time.']);
        }
        try {
            $result = $extractor->extract($resource, (int) $data['from_page'], (int) $data['to_page']);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['pdf' => $exception instanceof \RuntimeException ? $exception->getMessage() : 'The PDF could not be parsed. It may be scanned, encrypted, or use an unsupported layout.']);
        }
        $message = "Extracted {$result['added']} new question candidates; {$result['known']} already known. Nothing was published.";
        if ($result['empty']) {
            $message .= ' No supported questions on PDF pages: '.implode(', ', $result['empty']).'. These may be cover pages, scans, or unsupported layouts; check the original PDF.';
        }

        return redirect()->route('pdfQuestions', $resource)->with('success', $message);
    }

    public function show(Request $request, PdfQuestionImport $candidate)
    {
        $candidate->load('resource');
        $filters = CandidateReviewQuery::filters($request);
        $previous = CandidateReviewQuery::neighbor($candidate, $filters, false);
        $next = CandidateReviewQuery::neighbor($candidate, $filters);
        $similar = app(SimilarQuestions::class)->find($candidate);

        return view('admin.learning-content.pdf-question-review', compact('candidate', 'filters', 'previous', 'next', 'similar'));
    }

    public function review(Request $request, PdfQuestionImport $candidate)
    {
        $filters = CandidateReviewQuery::filters($request);
        $request->validate(['next' => ['nullable', 'boolean']]);
        $request->validate(['decision' => ['required', Rule::in(['Imported', 'Rejected'])]]);
        $data = [];
        if ($request->input('decision') === 'Imported') {
            $data = $request->validate([
                'question' => ['required', 'string', 'max:500'],
                'option1' => ['required', 'string', 'max:255'],
                'option2' => ['required', 'string', 'max:255', 'different:option1'],
                'option3' => ['required', 'string', 'max:255', 'different:option1,option2'],
                'option4' => ['required', 'string', 'max:255', 'different:option1,option2,option3'],
                'correctOption' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
                'category' => ['required', Rule::in(['General', 'Road Signs', 'Traffic Rules', 'Road Safety', 'Vehicle Knowledge'])],
                'difficulty' => ['required', Rule::in(['Easy', 'Medium', 'Hard'])],
                'explanation' => ['nullable', 'string', 'max:2000'],
                'confirmed' => ['accepted'],
            ]);
            unset($data['confirmed']);
        }
        DB::transaction(function () use ($candidate, $data, $request) {
            // Lock the source as well to serialize imports from the same bank.
            $source = LearningContentImport::lockForUpdate()->findOrFail($candidate->learning_content_import_id);
            $locked = PdfQuestionImport::lockForUpdate()->findOrFail($candidate->id);
            abort_unless($locked->status === 'Pending', 409, 'This candidate has already been reviewed.');
            if ($request->input('decision') === 'Imported') {
                abort_unless($source->status !== 'Rejected' && hash_equals($locked->source_hash, (string) $source->file_hash), 409, 'The source is rejected or has changed.');
                $disk = Storage::disk('learning-content');
                abort_unless($source->file_path && $disk->exists($source->file_path) && hash_equals($locked->source_hash, hash_file('sha256', $disk->path($source->file_path))), 409, 'The private source PDF is missing or has changed.');
                if (Question::withTrashed()->where('question_hash', hash('sha256', QuestionReviewIndex::normalize($data['question'])))->exists()) {
                    throw ValidationException::withMessages(['question' => 'This question already exists, including in trash.']);
                }
                $question = Question::create($data + ['status' => 'Draft']);
                $question->forceFill(['answer_verified_at' => now(), 'answer_verified_by' => auth()->id(), 'answer_review_hash' => ContentReadiness::answerHash($question)])->save();
                $locked->question_id = $question->id;
                $locked->payload = $data + array_filter($locked->payload, fn ($key) => str_starts_with($key, '_'), ARRAY_FILTER_USE_KEY);
            }
            $locked->status = $request->input('decision');
            $locked->reviewed_by = auth()->id();
            $locked->reviewed_at = now();
            $locked->save();
        });

        $next = $request->boolean('next') ? CandidateReviewQuery::neighbor($candidate, $filters) : null;

        return redirect()->route($next ? 'pdfQuestions.show' : 'pdfQuestions', ($next ? ['candidate' => $next] : ['resource' => $candidate->learning_content_import_id]) + $filters)->with('success', $request->input('decision') === 'Imported' ? 'Question saved as Draft. Check any required image before publishing from Questions.' : 'Candidate rejected.');
    }
}
