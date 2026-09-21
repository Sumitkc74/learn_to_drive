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
}
