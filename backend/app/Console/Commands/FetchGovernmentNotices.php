<?php

namespace App\Console\Commands;

use App\Services\DotmNoticeImporter;
use Illuminate\Console\Command;

class FetchGovernmentNotices extends Command
{
    protected $signature = 'notices:fetch-government';
    protected $description = 'Fetch new driving-related links from the official Nepal DoTM website for admin review';

    public function handle(DotmNoticeImporter $importer): int
    {
        try {
            $result = $importer->fetch();
            $this->info("Found {$result['found']}; added {$result['created']}; duplicates {$result['duplicates']}.");
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('The official notice check failed: '.$exception->getMessage());
            return self::FAILURE;
        }
    }
}
