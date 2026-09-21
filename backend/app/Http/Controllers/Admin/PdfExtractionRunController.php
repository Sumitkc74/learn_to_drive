<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ExtractPdfPage;
use App\Models\LearningContentImport;
use App\Models\PdfExtractionRun;
use App\Services\LocalPdfTools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PdfExtractionRunController extends Controller
{
    public function store(Request $request, LearningContentImport $resource, LocalPdfTools $tools)
    {
        $data = $request->validate(['mode' => ['required', 'in:text,ocr'], 'from_page' => ['required', 'integer', 'min:1', 'max:1000'], 'to_page' => ['required', 'integer', 'gte:from_page', 'max:1000']]);
        abort_if($data['to_page'] - $data['from_page'] >= 100, 422, 'Select at most 100 pages.');
        try {
            $tools->sourcePath($resource);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['source' => $e->getMessage()]);
        }
        $run = DB::transaction(function () use ($resource, $data) {
            $source = LearningContentImport::lockForUpdate()->findOrFail($resource->id);
            abort_if($source->status === 'Rejected', 409);
            abort_if(PdfExtractionRun::where('learning_content_import_id', $source->id)->whereIn('status', ['Queued', 'Running'])->exists(), 409, 'This bank already has an active extraction run.');
            $run = PdfExtractionRun::create($data + ['learning_content_import_id' => $source->id, 'source_hash' => $source->file_hash, 'next_page' => $data['from_page'], 'requested_by' => auth()->id()]);
            ExtractPdfPage::dispatch($run->id, $run->generation, $run->next_page);

            return $run;
        });

        return redirect()->route('pdfRuns.show', $run);
    }

    public function show(PdfExtractionRun $run)
    {
        $run->load('resource');

        return view('admin.learning-content.extraction-run', compact('run'));
    }

    public function status(PdfExtractionRun $run)
    {
        return response()->json($run->only(['status', 'from_page', 'to_page', 'next_page', 'added', 'known', 'cancel_requested', 'error', 'empty_pages']), 200, ['Cache-Control' => 'private, no-store']);
    }

    public function cancel(PdfExtractionRun $run)
    {
        DB::transaction(function () use ($run) {
            $run = PdfExtractionRun::lockForUpdate()->findOrFail($run->id);
            abort_unless(in_array($run->status, ['Queued', 'Running']), 409);
            $run->update(['cancel_requested' => true, 'status' => $run->status === 'Queued' ? 'Cancelled' : 'Running']);
        });

        return back()->with('success', 'Cancellation requested. A page already being processed may finish; no further pages will start.');
    }

    public function retry(PdfExtractionRun $run)
    {
        DB::transaction(function () use ($run) {
            LearningContentImport::lockForUpdate()->findOrFail($run->learning_content_import_id);
            $run = PdfExtractionRun::lockForUpdate()->findOrFail($run->id);
            abort_unless($run->status === 'Failed', 409);
            abort_if(PdfExtractionRun::where('learning_content_import_id', $run->learning_content_import_id)->whereIn('status', ['Queued', 'Running'])->exists(), 409, 'Another run is active.');
            $run->update(['generation' => $run->generation + 1, 'status' => 'Queued', 'error' => null, 'cancel_requested' => false]);
            ExtractPdfPage::dispatch($run->id, $run->generation, $run->next_page);
        });

        return back()->with('success','Retry queued from the unfinished page.');
    }
}
