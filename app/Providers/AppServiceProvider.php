<?php

namespace App\Providers;

use App\Models\Question;
use App\Models\User;
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
    }
}
