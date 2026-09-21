<?php
namespace App\Console\Commands;
class CreateLocalBackup extends \Illuminate\Console\Command {
    protected $signature='backup:local {--verify= : Verify an existing backup ID}';
    protected $description='Create and verify a private SQLite and upload backup, or recheck an existing backup';
    public function handle(\App\Services\LocalBackup $backup): int {
        try {$result=$this->option('verify') ? $backup->verify($this->option('verify')) : $backup->create();$this->info('Verified backup: '.$result['id']);return self::SUCCESS;}
        catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
    }
}
