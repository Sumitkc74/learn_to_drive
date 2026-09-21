<?php
namespace App\Console\Commands;
class CheckMediaStorage extends \Illuminate\Console\Command {
    protected $signature='media:check-storage';
    protected $description='Report missing and potentially unused uploads without deleting files';
    public function handle(\App\Services\MediaStorageAudit $audit): int {
        $report=$audit->scan();
        file_put_contents(storage_path('app/private/storage-check.json'),json_encode($report,JSON_PRETTY_PRINT));
        $this->info(count($report['issues']).' findings. Open Media Library > Storage Check for details. No files were deleted.');
        return self::SUCCESS;
    }
}
