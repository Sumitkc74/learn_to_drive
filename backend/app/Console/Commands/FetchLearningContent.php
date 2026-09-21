<?php

namespace App\Console\Commands;

use App\Services\LearningContentCollector;
use Illuminate\Console\Command;

class FetchLearningContent extends Command
{
    protected $signature = 'content:fetch-official {--source=all : Configured source key or all}';

    protected $description = 'Collect official learning resources into the pending review queue';

    public function handle(LearningContentCollector $collector): int
    {
        $keys = array_keys(config('official-content.sources'));
        $selected = $this->option('source');
        if ($selected !== 'all' && ! in_array($selected, $keys, true)) {
            $this->error('Unknown source. Choose all or: '.implode(', ', $keys));

            return self::FAILURE;
        }
        $failed = false;
        foreach ($selected === 'all' ? $keys : [$selected] as $key) {
            try {
                $result = $collector->fetch($key);
                $this->info("{$key}: {$result['added']} new, {$result['known']} already known, {$result['excluded']} excluded links.");
            } catch (\Throwable $exception) {
                $failed = true;
                report($exception);
                $this->warn($key.': source check failed. See the application log.');
            }
        }
        $this->info('New resources await admin review. No learner content was published.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
