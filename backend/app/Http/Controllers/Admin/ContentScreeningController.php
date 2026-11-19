<?php
namespace App\Http\Controllers\Admin;
class ContentScreeningController extends \App\Http\Controllers\Controller {
    public function retry(string $kind,int $id) {
        abort_unless(in_array($kind,['learning','notice']),404);
        $class=$kind==='learning' ? \App\Models\LearningContentImport::class : \App\Models\GovernmentNoticeImport::class;
        \Illuminate\Support\Facades\DB::transaction(function () use ($class, $id, $kind) {
        $record=$class::lockForUpdate()->findOrFail($id);
        abort_unless($record->status==='Pending',409);
        abort_if(in_array($record->ai_status,['Running','Queued']) && \Illuminate\Support\Carbon::parse($record->ai_activity_at ?? $record->updated_at)->gt(now()->subMinutes(10)),409,'Screening is already queued or running.');
        $record->forceFill(['ai_status'=>'Queued','ai_report'=>null,'ai_retry_count'=>0,'ai_activity_at'=>now()])->save();
        \App\Jobs\ScreenImportedContent::dispatch($kind,$id)->afterCommit();
        });
        return back()->with('success','AI screening queued. Human approval is still required.');
    }
    public function retryFailed() {
        $count=0;
        foreach (['learning'=>\App\Models\LearningContentImport::class,'notice'=>\App\Models\GovernmentNoticeImport::class] as $kind=>$class) {
            $class::where('status','Pending')->where('ai_status','Failed')->orderBy('id')->chunkById(100,function($rows) use($kind,$class,&$count) {
                foreach ($rows as $row) \Illuminate\Support\Facades\DB::transaction(function () use ($row,$kind,$class,&$count) {
                    $record=$class::lockForUpdate()->find($row->id);
                    if (!$record || $record->status!=='Pending' || $record->ai_status!=='Failed') return;
                    $record->forceFill(['ai_status'=>'Queued','ai_report'=>null,'ai_retry_count'=>0,'ai_activity_at'=>now()])->save();
                    \App\Jobs\ScreenImportedContent::dispatch($kind,$record->id)->afterCommit();
                    $count++;
                });
            });
        }
        return back()->with('success',$count===0 ? 'No failed AI screenings to retry.' : "Queued {$count} failed AI screening(s) for another attempt. Human approval is still required.");
    }
}
