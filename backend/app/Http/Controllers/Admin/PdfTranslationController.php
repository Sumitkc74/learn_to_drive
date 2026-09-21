<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\LearningContentImport;
use App\Models\PdfTranslation;
use App\Jobs\TranslatePdfPage;
use App\Services\LocalPdfTools;
use App\Services\PdfTextTranslator;
use App\Services\ReviewedTranslationPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
class PdfTranslationController extends Controller {
    public function upload(Request $request) {
        $data = $request->validate(['pdf' => ['required','file','mimes:pdf','max:'.min(20480, \App\Models\AppSetting::documentLimitKb())],
            'title' => ['required','string','max:255']]);
        $path = $request->file('pdf')->storeAs('', Str::uuid().'.pdf', 'learning-content');
        try {
            $resource = LearningContentImport::create(['source_key' => 'admin-upload', 'source_name' => 'Admin uploaded PDF',
                'source_url' => route('learningContent'), 'asset_url' => route('learningContent').'?upload='.Str::uuid(),
                'url_hash' => hash('sha256', $path), 'kind' => 'question-bank-document', 'title' => $data['title'], 'fetched_at' => now()]);
            $resource->forceFill(['file_path' => $path, 'file_hash' => hash_file('sha256', Storage::disk('learning-content')->path($path)),
                'file_size' => $request->file('pdf')->getSize(), 'mime_type' => 'application/pdf', 'downloaded_at' => now()])->save();
        } catch (\Throwable $e) { Storage::disk('learning-content')->delete($path); throw $e; }
        return redirect()->route('learningContent.show', $resource)->with('success', 'PDF saved privately. Choose its language to start translation.');
    }
    public function start(Request $request, LearningContentImport $resource, PdfTextTranslator $translator) {
        $data = $request->validate(['source_language' => ['required', Rule::in(['en','ne'])], 'confirmed' => ['accepted']]);
        abort_unless($translator->ready(), 422, 'Configure the translation provider first.');
        app(LocalPdfTools::class)->sourcePath($resource);
        $run = DB::transaction(function () use ($resource, $data) {
            $source = LearningContentImport::lockForUpdate()->findOrFail($resource->id);
            abort_if($source->status === 'Rejected', 409);
            abort_if(PdfTranslation::where('learning_content_import_id', $source->id)->where('status','!=','Completed')->exists(), 409, 'Open the existing translation instead.');
            $run = PdfTranslation::create(['learning_content_import_id' => $source->id, 'source_hash' => $source->file_hash,
                'source_language' => $data['source_language'], 'target_language' => $data['source_language'] === 'en' ? 'ne' : 'en', 'created_by' => auth()->id()]);
            TranslatePdfPage::dispatch($run->id, 1)->afterCommit();
            return $run;
        });
        return redirect()->route('pdfTranslations.show', $run);
    }
    public function show(PdfTranslation $translation) {
        $pages = $translation->pages()->paginate(1);
        return view('admin.learning-content.translation', compact('translation','pages'));
    }
    public function preview(Request $request, PdfTranslation $translation) {
        $data = $request->validate(['page' => ['required','integer','min:1','max:100']]);
        abort_unless(hash_equals($translation->source_hash, (string) $translation->resource->file_hash), 409);
        $path = app(LocalPdfTools::class)->preview($translation->resource, $data['page']);
        return Storage::disk('learning-content')->response($path, null, ['Content-Type' => 'image/png', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
    public function savePage(Request $request, PdfTranslation $translation) {
        $data = $request->validate(['page' => ['required','integer','min:1'], 'translated_text' => ['required','string','max:40000'], 'confirmed' => ['accepted'], 'version' => ['required','string','size:64']]);
        DB::transaction(function () use ($translation, $data) {
            $run = PdfTranslation::lockForUpdate()->findOrFail($translation->id);
            abort_unless($run->status === 'Review', 409);
            app(LocalPdfTools::class)->sourcePath($run->resource);
            abort_unless(hash_equals($run->source_hash, (string) $run->resource->file_hash), 409);
            $page = $run->pages()->where('page', $data['page'])->firstOrFail();
            abort_unless(hash_equals(hash('sha256', $page->translated_text), $data['version']), 409, 'Another reviewer changed this page. Reload it.');
            $page->update(['translated_text' => $data['translated_text'], 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        });
        return back()->with('success', 'Translation page reviewed.');
    }
    public function retry(PdfTranslation $translation) {
        DB::transaction(function () use ($translation) {
            $run = PdfTranslation::lockForUpdate()->findOrFail($translation->id);
            abort_unless($run->status === 'Failed', 409);
            $run->update(['status' => 'Queued', 'error' => null]);
            TranslatePdfPage::dispatch($run->id, $run->next_page)->afterCommit();
        });
        return back()->with('success', 'Translation queued for retry.');
    }
    public function finish(Request $request, PdfTranslation $translation, ReviewedTranslationPdf $generator) {
        set_time_limit(150);
        $request->validate(['confirmed' => ['accepted']]);
        $run = DB::transaction(function () use ($translation) {
            $run = PdfTranslation::lockForUpdate()->findOrFail($translation->id);
            abort_unless($run->status === 'Review' && $run->total_pages > 0 && $run->pages()->count() === $run->total_pages
                && !$run->pages()->whereNull('reviewed_at')->exists(), 409, 'Review every page first.');
            abort_unless($run->resource->status === 'Approved' && hash_equals($run->source_hash, (string) $run->resource->file_hash), 409, 'Approve the original reference first.');
            app(LocalPdfTools::class)->sourcePath($run->resource);
            abort_unless($run->resource->language === ($run->source_language === 'en' ? 'English' : 'Nepali'), 409, 'The approved source language must match the translation.');
            $run->update(['status' => 'Generating']);
            return $run;
        });
        $file = null;
        try {
            $file = $generator->generate($run);
            DB::transaction(function () use ($run, $file) {
                $resource = LearningContentImport::create(['source_key' => 'reviewed-translation', 'source_name' => 'Reviewed translation of '.$run->resource->source_name,
                    'source_url' => $run->resource->source_url, 'asset_url' => route('pdfTranslations.show', $run),
                    'url_hash' => hash('sha256', 'translation:'.$run->id), 'kind' => 'question-bank-document',
                    'title' => mb_substr('Reviewed translation: '.$run->resource->title, 0, 500), 'fetched_at' => now()]);
                $resource->forceFill($file + ['status' => 'Approved', 'language' => $run->target_language === 'ne' ? 'Nepali' : 'English',
                    'licence_category' => $run->resource->licence_category, 'edition' => $run->resource->edition,
                    'review_notes' => 'Admin-reviewed translation, including original page images. Not an official translated edition.',
                    'reviewed_by' => auth()->id(), 'reviewed_at' => now()])->save();
                $run->update(['status' => 'Completed', 'output_resource_id' => $resource->id]);
            });
        } catch (\Throwable $e) {
            if ($file) Storage::disk('learning-content')->delete($file['file_path']);
            $run->update(['status' => 'Review']);
            return back()->withErrors(['translation' => 'PDF generation failed. Check the configured browser and document size limit, then retry.']);
        }
        return redirect()->route('learningContent.show', $run->fresh()->output_resource_id)->with('success', 'Reviewed translation PDF is ready. Pair it with the original using Add to Question Banks.');
    }
}
