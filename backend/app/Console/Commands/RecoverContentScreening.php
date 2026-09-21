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
        foreach (['learning'=>LearningContentImport::class,'notice'=>GovernmentNoticeImport::class] as $kind=>$class) {
            $class::where('status','Pending')->whereIn('ai_status',['Queued','Running','Failed','Deferred'])->orderBy('id')->chunkById(100,function($rows) use($kind,$class,&$count) {
                foreach ($rows as $row) DB::transaction(function() use($row,$kind,$class,&$count) {
                    $record=$class::lockForUpdate()->find($row->id);
                    if (!$record || $record->status!=='Pending' || !in_array($record->ai_status,['Queued','Running','Failed','Deferred'])) return;
                    $last=\Illuminate\Support\Carbon::parse($record->ai_activity_at ?? $record->updated_at);
                    if ($record->ai_status==='Deferred') {
                        if ($last->copy()->timezone('Asia/Kathmandu')->toDateString() >= now('Asia/Kathmandu')->toDateString()) return;
                        $record->forceFill(['ai_status'=>'Queued','ai_activity_at'=>now()])->save();
                        ScreenImportedContent::dispatch($kind,$record->id)->afterCommit(); $count++; return;
                    }
                    if ($last->gt(now()->subMinutes(10))) return;
                    if ($record->ai_retry_count>=3) {
                        if ($record->ai_status!=='Failed') $record->forceFill(['ai_status'=>'Failed','ai_report'=>'Automatic retry limit reached. Check the provider and worker, then retry manually.','ai_activity_at'=>now()])->save();
                        return;
                    }
                    $record->forceFill(['ai_status'=>'Queued','ai_retry_count'=>$record->ai_retry_count+1,'ai_activity_at'=>now(),'ai_report'=>'Automatically queued after a failed or stalled screening.'])->save();
                    ScreenImportedContent::dispatch($kind,$record->id)->afterCommit();
                    $count++;
                });
            });
        }
        $this->info("Recovered: {$count} AI checks. Human approval remains required.");
        return self::SUCCESS;
    }
}
