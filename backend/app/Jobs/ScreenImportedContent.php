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

    /**
     * Gap between consecutive screening calls when a batch is queued, so a
     * bulk retry (or a deferred resume) does not hit Gemini as a burst of
     * back-to-back calls that the free tier answers with HTTP 503.
     */
    public const STAGGER_SECONDS = 15;

    /**
     * Longest stagger for one batch. Beyond this, later items share the
     * slot and run through the single worker in turn.
     */
    public const MAX_STAGGER_SECONDS = 600;

    /** Delay, in seconds, for the $index-th job in a batch (0-based). */
    public static function staggerDelay(int $index): int {
        return min(max(0, $index) * self::STAGGER_SECONDS, self::MAX_STAGGER_SECONDS);
    }

    /**
     * Delay, in seconds, before the $retryCount-th automatic retry of a
     * transient provider error: 2 minutes, then 4, then 8 minutes.
     */
    public static function backoffDelay(int $retryCount): int {
        return 60 * (2 ** max(1, $retryCount));
    }

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
