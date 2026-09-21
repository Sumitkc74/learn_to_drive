<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
class CheckSourceRevision implements ShouldQueue {
    use Dispatchable,Queueable;
    public $timeout=60;
    public $tries=1;
    public function __construct(public int $id){$this->onConnection('pdf-extraction')->onQueue('pdf-extraction');}
    public function middleware(): array {return [(new \Illuminate\Queue\Middleware\WithoutOverlapping('source-revision-'.$this->id))->dontRelease()->expireAfter(120)];}
    public function handle(\App\Services\SourceRevisionCheck $checker): void {
        if($resource=\App\Models\LearningContentImport::find($this->id)) $checker->check($resource);
    }
}
