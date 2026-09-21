<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
class ScreenImportedContent implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 150;
    public $tries = 1;
    public function __construct(public string $kind, public int $id) {
        $this->onConnection('pdf-extraction')->onQueue('pdf-extraction');
    }
    public function handle(\App\Services\ContentAiScreening $service): void {
        $class = $this->kind === 'notice' ? \App\Models\GovernmentNoticeImport::class : \App\Models\LearningContentImport::class;
        $record = $class::find($this->id);
        if ($record && $record->status === 'Pending' && $record->ai_status === 'Queued') $service->screen($record);
    }
    public function middleware(): array {
        return [(new \Illuminate\Queue\Middleware\WithoutOverlapping('content-ai-'.$this->kind.'-'.$this->id))->dontRelease()->expireAfter(180)];
    }
    public function failed(?\Throwable $error): void {
        $class = $this->kind === 'notice' ? \App\Models\GovernmentNoticeImport::class : \App\Models\LearningContentImport::class;
        $class::where('id',$this->id)->where('ai_status','Running')->update(['ai_status'=>'Failed','ai_activity_at'=>now(),'ai_report'=>'AI screening stopped. Retry the screening.']);
    }
}
