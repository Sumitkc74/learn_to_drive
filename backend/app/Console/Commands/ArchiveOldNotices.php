<?php
namespace App\Console\Commands;
use App\Models\Notice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class ArchiveOldNotices extends Command {
    protected $signature = 'notices:archive-old {--dry-run : Show eligible notices without changing them}';
    protected $description = 'Archive expired notices and older published notices without an expiry date';
    public function handle(): int {
        $days = (int) config('notices.archive_after_days');
        if ($days < 1) { $this->error('Archive age must be at least one day.'); return self::FAILURE; }
        $count = 0;
        Notice::where('status', 'Published')->orderBy('id')->chunkById(100, function ($notices) use ($days, &$count) {
            foreach ($notices as $notice) DB::transaction(function () use ($notice, $days, &$count) {
                $current = Notice::lockForUpdate()->find($notice->id);
                if (!$current || $current->status !== 'Published' || $current->publish_at?->isFuture()) return;
                $eligible = $current->expires_at ? $current->expires_at->lte(now())
                    : ($current->publish_at ?? $current->created_at)->lte(now()->subDays($days));
                if (!$eligible) return;
                $count++;
                if (!$this->option('dry-run')) $current->update(['status' => 'Archived']);
            });
        });
        $this->info(($this->option('dry-run') ? 'Eligible: ' : 'Archived: ').$count.' notices.');
        return self::SUCCESS;
    }
}
