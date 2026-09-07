<?php

namespace App\Providers;

use App\Models\Question;
use App\Models\User;
use App\Models\AppSetting;
use App\Models\Notice;
use App\Observers\AdminAuditObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(AdminAuditObserver::class);
        Question::observe(AdminAuditObserver::class);
        AppSetting::observe(AdminAuditObserver::class);
        Notice::observe(AdminAuditObserver::class);
    }
}
