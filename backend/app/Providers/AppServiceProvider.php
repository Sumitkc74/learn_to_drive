<?php

namespace App\Providers;

use App\Models\AppSetting;
use App\Models\GovernmentNoticeImport;
use App\Models\LearningContentImport;
use App\Models\Notice;
use App\Models\PdfExtractionRun;
use App\Models\PdfQuestionImport;
use App\Models\Question;
use App\Models\User;
use App\Observers\AdminAuditObserver;
use App\Observers\QuestionMediaReadinessObserver;
use App\Services\AdminSystemHealth;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
        Queue::looping(function (Looping $event) {
            if ($event->connectionName === 'pdf-extraction') {
                app(AdminSystemHealth::class)->heartbeat();
            }
        });
        GovernmentNoticeImport::observe(\App\Observers\ContentScreeningObserver::class);
        LearningContentImport::observe(\App\Observers\ContentScreeningObserver::class);
        foreach (\App\Support\VersionedContent::TYPES as [$contentClass]) $contentClass::observe(\App\Observers\ContentVersionObserver::class);
        User::observe(AdminAuditObserver::class);
        Question::observe(AdminAuditObserver::class);
        Media::observe(QuestionMediaReadinessObserver::class);
        AppSetting::observe(AdminAuditObserver::class);
        Notice::observe(AdminAuditObserver::class);
        GovernmentNoticeImport::observe(AdminAuditObserver::class);
        LearningContentImport::observe(AdminAuditObserver::class);
        PdfQuestionImport::observe(AdminAuditObserver::class);
        PdfExtractionRun::observe(AdminAuditObserver::class);
    }
}
