<?php

namespace App\Console\Commands;

use App\Services\OfficialContentPreview;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PreviewOfficialContent extends Command
{
    protected $signature = 'content:preview-official {--source=all : Configured source key or all} {--save : Save a local JSON preview report}';

    protected $description = 'Discover official learning resources without importing or publishing records';

    public function handle(OfficialContentPreview $preview): int
    {
        $sources = config('official-content.sources');
        $selected = $this->option('source');
        if ($selected !== 'all' && ! isset($sources[$selected])) {
            $this->error('Unknown source. Choose: all, '.implode(', ', array_keys($sources)));

            return self::FAILURE;
        }
        $report = ['checked_at' => now()->toIso8601String(), 'mode' => 'preview-only', 'sources' => []];
        $failed = false;
        foreach ($selected === 'all' ? $sources : [$selected => $sources[$selected]] as $key => $source) {
            try {
                $result = $preview->fetch($source);
                $report['sources'][$key] = ['source_url' => $source['url'], 'status' => 'checked'] + $result;
                $this->info($key.': '.count($result['assets']).' resources; '.count($result['excluded_assets']).' excluded links.');
                foreach ($result['assets'] as $asset) {
                    $this->line('  '.$asset['asset_url']);
                }
            } catch (\Throwable $exception) {
                $failed = true;
                $report['sources'][$key] = ['source_url' => $source['url'], 'status' => 'failed', 'error' => $exception->getMessage()];
                $this->warn($key.': '.$exception->getMessage());
            }
        }
        if ($this->option('save')) {
            $path = 'private/official-content-preview.json';
            Storage::disk('local')->put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $this->info('Preview saved: '.Storage::disk('local')->path($path));
        }
        $this->info('No database records were created or published. Documents and images still require review.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
