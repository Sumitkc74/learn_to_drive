<?php
namespace App\Observers;
class ContentScreeningObserver {
    public function creating($record): void {
        if (config('content-screening.enabled')) $record->ai_status = 'Queued';
    }
    public function created($record): void {
        if ($record->ai_status === 'Queued') \App\Jobs\ScreenImportedContent::dispatch($record instanceof \App\Models\GovernmentNoticeImport ? 'notice' : 'learning', $record->id)->afterCommit();
    }
}
