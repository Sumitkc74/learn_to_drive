<?php
namespace App\Console\Commands;
class ResetDemoPreview extends \Illuminate\Console\Command {
    protected $signature='demo:reset {--apply : Restore saved demo fields; otherwise preview only}';
    protected $description='Preview or restore the local demo baseline without deleting accounts, uploads or new records';
    public function handle(\App\Services\DemoPreviewState $state):int {
        try{$count=$state->restore((bool)$this->option('apply'));}
        catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
        $this->info($count.' demo rows '.($this->option('apply')?'restored.':'would be restored. Use --apply to proceed.'));
        return self::SUCCESS;
    }
}
