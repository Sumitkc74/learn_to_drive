<?php

namespace App\Services;

use App\Models\LearningContentImport;
use App\Models\PdfExtractionRun;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminSystemHealth
{
    public const HEARTBEAT = 'admin-health:pdf-worker';

    public function heartbeat(): void
    {
        $last = Cache::get(self::HEARTBEAT);
        if (! $last || now()->timestamp - (int) $last >= 30) {
            Cache::put(self::HEARTBEAT, now()->timestamp, 600);
        }
    }

    public function snapshot(): array
    {
        $seen = Cache::get(self::HEARTBEAT);
        $age = $seen ? max(0, now()->timestamp - (int) $seen) : null;
        $root = Storage::disk('learning-content')->path('');
        $probe = is_dir($root) ? $root : storage_path('app');
        $free = @disk_free_space($probe);
        $total = @disk_total_space($probe);
        $sourceChecks = DB::table('official_source_checks')->get()->keyBy('source_key');
        $sources = [];
        foreach (config('official-content.sources') as $key => $source) {
            $check = $sourceChecks->get($key);
            $sources[] = ['name' => $source['name'], 'status' => $check?->status ?? 'Never checked', 'checked_at' => $check?->checked_at, 'found' => $check?->found];
        }

        return [
            'worker_seen_at' => $seen, 'worker_recent' => $age !== null && $age <= 180,
            'worker_status' => $age === null ? 'No heartbeat recorded' : ($age <= 180 ? 'Heartbeat received recently' : 'Heartbeat is stale'),
            'queued' => DB::table('pdf_extraction_jobs')->whereNull('reserved_at')->count(),
            'failed' => PdfExtractionRun::where('status', 'Failed')->count(),
            'stalled' => PdfExtractionRun::whereIn('status', ['Queued', 'Running'])->where('updated_at', '<', now()->subMinutes(5))->count(),
            'storage_writable' => is_writable($probe), 'storage_free' => $free === false ? null : $free,
            'storage_total' => $total === false ? null : $total,
            'resource_bytes' => (int) LearningContentImport::sum('file_size'),
            'sources' => $sources,
        ];
    }
}
