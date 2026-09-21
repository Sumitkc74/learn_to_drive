<?php

namespace App\Console\Commands;

use App\Models\LearningContentImport;
use App\Services\NepaliQuestionOcr;
use Illuminate\Console\Command;

class OcrPdfQuestions extends Command
{
    protected $signature = 'content:ocr-questions {resource : Learning-content resource ID} {--from=5} {--to=5}';

    protected $description = 'Extract Nepali question candidates using local OCR, one page at a time';

    public function handle(NepaliQuestionOcr $ocr): int
    {
        $from = filter_var($this->option('from'), FILTER_VALIDATE_INT);
        $to = filter_var($this->option('to'), FILTER_VALIDATE_INT);
        $resource = LearningContentImport::find($this->argument('resource'));
        if (! $resource || ! $from || ! $to || $from < 1 || $to < $from || $to > 1000 || $to - $from >= 100) {
            $this->error('Provide an existing resource and a page range of at most 100 pages.');

            return self::FAILURE;
        }
        for ($page = $from; $page <= $to; $page++) {
            try {
                $result = $ocr->extract($resource, $page);
            } catch (\Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }
            $this->line("Page {$page}: {$result['added']} new, {$result['known']} known, {$result['skipped']} header or unrecognized rows skipped.");
            if ($page >= $result['pages']) {
                break;
            }
        }

        return self::SUCCESS;
    }
}
