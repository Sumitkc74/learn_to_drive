<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('content:check-revisions')->dailyAt('03:00')->timezone('Asia/Kathmandu')->withoutOverlapping();
        $schedule->command('content:recover-screening')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('notices:archive-old')->dailyAt('02:20')->timezone('Asia/Kathmandu')->withoutOverlapping();
        if (\Illuminate\Support\Facades\Schema::hasTable('scraping_sources')) {
            foreach(app(\App\Services\ScrapingSourceSettings::class)->all() as $key=>$source) {
                if (!$source['enabled']) continue;
                $command=$key==='government-notices' ? 'notices:fetch-government' : 'content:fetch-official --source='.$key;
                $schedule->command($command)->dailyAt($source['daily_time'])->timezone('Asia/Kathmandu')->withoutOverlapping();
            }
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
