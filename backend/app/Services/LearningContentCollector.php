<?php

namespace App\Services;

use App\Models\LearningContentImport;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LearningContentCollector
{
    public function __construct(private OfficialContentPreview $preview) {}

    public function fetch(string $sourceKey): array
    {
        if (!app(ScrapingSourceSettings::class)->enabled($sourceKey)) return ['added'=>0,'known'=>0,'excluded'=>0];
        return app(ContentSourceHistory::class)->run($sourceKey, fn()=> $this->collect($sourceKey));
    }
    private function collect(string $sourceKey): array
    {
        $source = config('official-content.sources')[$sourceKey] ?? null;
        if (! $source) {
            throw new InvalidArgumentException('Unknown official source.');
        }
        try {
            $result = $this->preview->fetch($source);
        } catch (\Throwable $exception) {
            DB::table('official_source_checks')->updateOrInsert(['source_key' => $sourceKey], ['status' => 'Failed', 'checked_at' => now(), 'found' => 0]);
            throw $exception;
        }
        DB::table('official_source_checks')->updateOrInsert(['source_key' => $sourceKey], ['status' => 'Success', 'checked_at' => now(), 'found' => count($result['assets'])]);
        $added = 0;
        foreach ($result['assets'] as $asset) {
            $title = $asset['title'];
            if ($title === $source['name']) {
                $title = rawurldecode(basename(parse_url($asset['asset_url'], PHP_URL_PATH)));
            }
            $record = LearningContentImport::firstOrCreate(['url_hash' => hash('sha256', $asset['asset_url'])], [
                'source_key' => $sourceKey,
                'source_name' => $asset['source_name'],
                'source_url' => $asset['source_url'],
                'asset_url' => $asset['asset_url'],
                'title' => mb_substr($title, 0, 500),
                'kind' => $asset['kind'],
                'fetched_at' => now(),
            ]);
            $added += (int) $record->wasRecentlyCreated;
        }

        return ['added' => $added, 'known' => count($result['assets']) - $added, 'excluded' => count($result['excluded_assets'])];
    }
}
