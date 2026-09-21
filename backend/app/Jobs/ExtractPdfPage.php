<?php

namespace App\Jobs;

use App\Models\PdfExtractionRun;
use App\Services\NepaliQuestionOcr;
use App\Services\PdfQuestionExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ExtractPdfPage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 150;

    public function __construct(public int $runId, public int $generation, public int $page)
    {
        $this->onConnection('pdf-extraction');
    }

    public function backoff(): array
    {
        return [10, 30];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('pdf-run:'.$this->runId))->releaseAfter(5)->expireAfter(180)];
    }

    public function handle(PdfQuestionExtractor $text, NepaliQuestionOcr $ocr): void
    {
        $run = DB::transaction(function () {
            $run = PdfExtractionRun::lockForUpdate()->find($this->runId);
            if (! $run || $run->generation !== $this->generation || $run->next_page !== $this->page || ! in_array($run->status, ['Queued', 'Running'], true)) {
                return null;
            }
            if ($run->cancel_requested) {
                $run->update(['status' => 'Cancelled']);

                return null;
            }
            $run->update(['status' => 'Running']);

            return $run;
        });
        if (! $run) {
            return;
        }
        $source = $run->resource;
        if (! $source || $source->status === 'Rejected' || ! hash_equals($run->source_hash, (string) $source->file_hash)) {
            throw new \RuntimeException('The source was changed or rejected.');
        }
        $result = $run->mode === 'ocr' ? $ocr->extract($source, $this->page) : $text->extract($source, $this->page, $this->page);
        DB::transaction(function () use ($result) {
            $run = PdfExtractionRun::lockForUpdate()->findOrFail($this->runId);
            if ($run->generation !== $this->generation || $run->next_page !== $this->page) {
                return;
            }
            $run->added += $result['added'];
            $run->known += $result['known'];
            $run->to_page = min($run->to_page, $result['pages'] ?? $run->to_page);
            $run->next_page = $this->page + 1;
            if (! ($result['added'] + $result['known'])) {
                $run->empty_pages = array_merge($run->empty_pages ?? [], [$this->page]);
            }
            $run->status = $run->cancel_requested ? 'Cancelled' : ($run->next_page > $run->to_page ? 'Completed' : 'Queued');
            $run->save();
            if ($run->status === 'Queued') {
                self::dispatch($run->id, $run->generation, $run->next_page);
            }
        });
    }

    public function failed(?\Throwable $exception): void
    {
        PdfExtractionRun::whereKey($this->runId)->where('generation', $this->generation)->whereIn('status', ['Queued', 'Running'])
            ->update(['status' => 'Failed', 'error' => 'This page could not be processed after retries. Check the source and local extraction tools, then retry.']);
    }
}
