<?php
namespace App\Console\Commands;
use App\Jobs\ScreenImportedContent;
use App\Models\{LearningContentImport,GovernmentNoticeImport};
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
class RecoverContentScreening extends Command {
    protected $signature='content:recover-screening';
    protected $description='Recover stalled AI checks with at most three automatic retries';
    public function handle(): int {
        $count=0;
        // One stagger sequence for the whole run, across both kinds, so a
        // deferred resume or a catch-up batch spreads its Gemini calls out
        // instead of firing them back-to-back.
        $index=0;
        foreach (['learning'=>LearningContentImport::class,'notice'=>GovernmentNoticeImport::class] as $kind=>$class) {
            $class::where('status','Pending')->whereIn('ai_status',['Queued','Running','Failed','Deferred'])->orderBy('id')->chunkById(100,function($rows) use($kind,$class,&$count,&$index) {
                foreach ($rows as $row) DB::transaction(function() use($row,$kind,$class,&$count,&$index) {
                    $record=$class::lockForUpdate()->find($row->id);
                    if (!$record || $record->status!=='Pending' || !in_array($record->ai_status,['Queued','Running','Failed','Deferred'])) return;
                    $last=\Illuminate\Support\Carbon::parse($record->ai_activity_at ?? $record->updated_at);
                    if ($record->ai_status==='Deferred') {
                        if ($last->copy()->timezone('Asia/Kathmandu')->toDateString() >= now('Asia/Kathmandu')->toDateString()) return;
                        // A new Nepal day means a fresh retry budget: an item
                        // that burned its three automatic retries before it
                        // was deferred gets three more, instead of failing
                        // once more and sticking at Failed forever.
                        // ai_activity_at is the run time, not now: the
                        // ten-minute gate above is measured from it, so the
                        // recovery command does not re-queue a job that is
                        // simply waiting for its staggered slot.
                        $delay=ScreenImportedContent::staggerDelay($index++);
                        $record->forceFill(['ai_status'=>'Queued','ai_retry_count'=>0,'ai_activity_at'=>now()->addSeconds($delay)])->save();
                        ScreenImportedContent::dispatch($kind,$record->id)->delay($delay)->afterCommit(); $count++; return;
                    }
                    if ($last->gt(now()->subMinutes(10))) return;
                    if ($record->ai_retry_count>=3) {
                        if ($record->ai_status!=='Failed') $record->forceFill(['ai_status'=>'Failed','ai_report'=>'Automatic retry limit reached. Check the provider and worker, then retry manually.','ai_activity_at'=>now()])->save();
                        return;
                    }
                    $delay=ScreenImportedContent::staggerDelay($index++);
                    $record->forceFill(['ai_status'=>'Queued','ai_retry_count'=>$record->ai_retry_count+1,'ai_activity_at'=>now()->addSeconds($delay),'ai_report'=>'Automatically queued after a failed or stalled screening.'])->save();
                    ScreenImportedContent::dispatch($kind,$record->id)->delay($delay)->afterCommit();
                    $count++;
                });
            });
        }
        $this->info("Recovered: {$count} AI checks. Human approval remains required.");
        return self::SUCCESS;
    }
}
