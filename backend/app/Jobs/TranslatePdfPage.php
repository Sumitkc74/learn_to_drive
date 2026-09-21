<?php
namespace App\Jobs;
use App\Models\PdfTranslation;
use App\Services\LocalPdfTools;
use App\Services\PdfTextTranslator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
class TranslatePdfPage implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 1;
    public int $timeout = 150;
    public function __construct(public int $translationId, public int $page) { $this->onConnection('pdf-extraction'); }
    public function middleware(): array { return [(new WithoutOverlapping('translation:'.$this->translationId))->releaseAfter(5)->expireAfter(180)]; }
    public function handle(LocalPdfTools $tools, PdfTextTranslator $translator): void {
        $run = PdfTranslation::findOrFail($this->translationId);
        if (!in_array($run->status, ['Queued','Running'], true) || $run->next_page != $this->page) return;
        $failure = 'The source PDF is missing, changed, or rejected. Check its saved copy before retrying.';
        try {
            if ($run->resource->status === 'Rejected' || !hash_equals($run->source_hash, (string) $run->resource->file_hash)) throw new \RuntimeException('Source changed.');
            $path = $tools->sourcePath($run->resource);
            $run->update(['status' => 'Running', 'error' => null]);
            $failure = 'Text extraction failed on page '.$this->page.'. Check the page and the local OCR setup, then retry.';
            $result = $tools->run('translate-text', ['--pdf', $path, '--page', (string) $this->page]);
            if ($result['pages'] > 100) {
                $failure = 'This PDF exceeds the 100-page translation limit. Upload a smaller document.';
                throw new \RuntimeException('Maximum 100 pages.');
            }
            $failure = 'Translation failed on page '.$this->page.'. Check your provider configuration, model availability, and API quota, then retry.';
            $translated = $translator->translate($result['text'], $run->source_language, $run->target_language);
            \Illuminate\Support\Facades\DB::transaction(function () use ($run, $result, $translated) {
                $locked = PdfTranslation::lockForUpdate()->findOrFail($run->id);
                if (!in_array($locked->status, ['Queued','Running'], true) || $locked->next_page != $this->page) return;
                $locked->pages()->firstOrCreate(['page' => $this->page], ['source_text' => $result['text'], 'translated_text' => $translated]);
                $locked->update(['total_pages' => $result['pages'], 'next_page' => $this->page + 1,
                    'status' => $this->page >= $result['pages'] ? 'Review' : 'Running']);
                if ($locked->status === 'Running') self::dispatch($locked->id, $this->page + 1)->afterCommit();
            });
        } catch (\Throwable $e) { $run->update(['status' => 'Failed', 'error' => $failure]); }
    }
    public function failed(?\Throwable $e): void { PdfTranslation::whereKey($this->translationId)->whereIn('status', ['Queued','Running'])->update(['status' => 'Failed', 'error' => 'The worker stopped or timed out. Check the PDF worker and retry the unfinished page.']); }
}
