<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TrafficSignController;
use App\Http\Controllers\Admin\ExamPaperController;
use App\Http\Controllers\Admin\VisionTestController;
use App\Http\Controllers\Admin\ExamInformationController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\TutorialController;
use App\Http\Controllers\Admin\NoticeController;
use App\Http\Controllers\Admin\NoticeImportController;
use App\Http\Controllers\Admin\ProfileVerificationController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AppSettingController;
use App\Http\Controllers\Admin\QuestionImportController;
use App\Http\Controllers\Admin\GovernmentNoticeImportController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/media/published/{media}', [\App\Http\Controllers\ProtectedMediaController::class, 'show'])->whereNumber('media')->name('media.published');
Route::get('/admin/media/review/{media}', [\App\Http\Controllers\ProtectedMediaController::class, 'show'])->whereNumber('media')->middleware(['auth', \App\Http\Middleware\AuthenticateAdmin::class])->name('media.review');

require __DIR__.'/learner.php';

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::post('/login', [LoginController::class, 'login'])->name('login');

// Route::get('/admin', [HomeController::class, 'index'])->middleware('admin');

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/media-library', [\App\Http\Controllers\Admin\MediaLibraryController::class, 'index'])->name('mediaLibrary');
    Route::get('/admin/import-progress', [\App\Http\Controllers\Admin\ImportProgressController::class, 'index'])->name('importProgress');
    Route::get('/admin/website-import', [\App\Http\Controllers\Admin\WebsiteImportController::class, 'index'])->name('websiteImport');
    Route::post('/admin/website-import/scan', [\App\Http\Controllers\Admin\WebsiteImportController::class, 'scan'])->middleware('throttle:4,10')->name('websiteImport.scan');
    Route::post('/admin/website-import/add', [\App\Http\Controllers\Admin\WebsiteImportController::class, 'store'])->name('websiteImport.store');
    Route::post('/admin/question-translation', [\App\Http\Controllers\Admin\QuestionTranslationController::class, 'translate'])->middleware('throttle:60,10')->name('questionTranslation');
    Route::post('/admin/learning-content/{resource}/check-revision', [\App\Http\Controllers\Admin\SourceRevisionController::class, 'check'])->middleware('throttle:2,10')->name('learningContent.checkRevision');
    Route::get('/admin/backups', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('backups');
    Route::post('/admin/backups', [\App\Http\Controllers\Admin\BackupController::class, 'create'])->middleware('throttle:2,10')->name('backups.create');
    Route::post('/admin/backups/{id}/verify', [\App\Http\Controllers\Admin\BackupController::class, 'verify'])->middleware('throttle:5,10')->name('backups.verify');
    Route::get('/admin/content-versions/{type}/{id}', [\App\Http\Controllers\Admin\ContentVersionController::class, 'index'])->whereNumber('id')->name('contentVersions');
    Route::post('/admin/content-versions/{type}/{id}/{version}/restore', [\App\Http\Controllers\Admin\ContentVersionController::class, 'restore'])->whereNumber('id')->whereNumber('version')->name('contentVersions.restore');
    Route::get('/admin/review-operations', [\App\Http\Controllers\Admin\ReviewOperationsController::class, 'index'])->name('reviewOperations');
    Route::post('/admin/review-operations', [\App\Http\Controllers\Admin\ReviewOperationsController::class, 'update'])->name('reviewOperations.update');
    Route::get('/admin/scraping-sources', [\App\Http\Controllers\Admin\ScrapingSourceController::class, 'index'])->name('scrapingSources');
    Route::post('/admin/scraping-sources/{key}', [\App\Http\Controllers\Admin\ScrapingSourceController::class, 'update'])->name('scrapingSources.update');
    Route::get('/admin/storage-check', [\App\Http\Controllers\Admin\StorageCheckController::class, 'index'])->name('storageCheck');
    Route::post('/admin/storage-check', [\App\Http\Controllers\Admin\StorageCheckController::class, 'scan'])->middleware('throttle:3,1')->name('storageCheck.scan');
    Route::get('/admin/import-history', [\App\Http\Controllers\Admin\ImportHistoryController::class, 'index'])->name('importHistory');
    Route::post('/admin/content-screening/{kind}/{id}', [\App\Http\Controllers\Admin\ContentScreeningController::class, 'retry'])->whereNumber('id')->middleware('throttle:10,1')->name('contentScreening.retry');
    Route::post('/admin/learning-content/upload-pdf', [\App\Http\Controllers\Admin\PdfTranslationController::class, 'upload'])->middleware('throttle:5,10')->name('pdfTranslations.upload');
    Route::post('/admin/learning-content/{resource}/translate', [\App\Http\Controllers\Admin\PdfTranslationController::class, 'start'])->whereNumber('resource')->middleware('throttle:3,10')->name('pdfTranslations.start');
    Route::get('/admin/pdf-translations/{translation}', [\App\Http\Controllers\Admin\PdfTranslationController::class, 'show'])->name('pdfTranslations.show');
    Route::get('/admin/pdf-translations/{translation}/preview', [\App\Http\Controllers\Admin\PdfTranslationController::class, 'preview'])->name('pdfTranslations.preview');
    Route::post('/admin/pdf-translations/{translation}/page', [\App\Http\Controllers\Admin\PdfTranslationController::class, 'savePage'])->name('pdfTranslations.page');
    Route::post('/admin/pdf-translations/{translation}/retry', [\App\Http\Controllers\Admin\PdfTranslationController::class, 'retry'])->name('pdfTranslations.retry');
    Route::post('/admin/pdf-translations/{translation}/finish', [\App\Http\Controllers\Admin\PdfTranslationController::class, 'finish'])->name('pdfTranslations.finish');
    Route::post('/admin/learning-content/{resource}/exam-paper', [\App\Http\Controllers\Admin\ReviewedExamPaperController::class, 'store'])->whereNumber('resource')->name('learningContent.storeExamPaper');
    Route::get('/admin/support', [\App\Http\Controllers\Admin\SupportController::class, 'index'])->name('support.index');
    Route::get('/admin/support/{ticket}', [\App\Http\Controllers\Admin\SupportController::class, 'show'])->whereNumber('ticket')->name('support.show');
    Route::post('/admin/support/{ticket}', [\App\Http\Controllers\Admin\SupportController::class, 'update'])->whereNumber('ticket')->name('support.update');
    Route::get('/admin/learning-content', [\App\Http\Controllers\Admin\LearningContentController::class, 'index'])->name('learningContent');
    Route::get('/admin/content-readiness',[\App\Http\Controllers\Admin\ContentReadinessController::class,'index'])->name('contentReadiness');
    Route::post('/admin/content-readiness/{question}/verify',[\App\Http\Controllers\Admin\ContentReadinessController::class,'verify'])->whereNumber('question')->name('contentReadiness.verify');
    Route::post('/admin/learning-content/{resource}/runs', [\App\Http\Controllers\Admin\PdfExtractionRunController::class,'store'])->whereNumber('resource')->middleware('throttle:6,10')->name('pdfRuns.store');
    Route::get('/admin/extraction-runs/{run}', [\App\Http\Controllers\Admin\PdfExtractionRunController::class,'show'])->whereNumber('run')->name('pdfRuns.show');
    Route::get('/admin/extraction-runs/{run}/status', [\App\Http\Controllers\Admin\PdfExtractionRunController::class,'status'])->whereNumber('run')->name('pdfRuns.status');
    Route::post('/admin/extraction-runs/{run}/cancel', [\App\Http\Controllers\Admin\PdfExtractionRunController::class,'cancel'])->whereNumber('run')->name('pdfRuns.cancel');
    Route::post('/admin/extraction-runs/{run}/retry', [\App\Http\Controllers\Admin\PdfExtractionRunController::class,'retry'])->whereNumber('run')->middleware('throttle:6,10')->name('pdfRuns.retry');
    Route::get('/admin/learning-content/{resource}/questions', [\App\Http\Controllers\Admin\PdfQuestionController::class, 'index'])->whereNumber('resource')->name('pdfQuestions');
    Route::post('/admin/learning-content/{resource}/questions/extract', [\App\Http\Controllers\Admin\PdfQuestionController::class, 'extract'])->whereNumber('resource')->middleware('throttle:3,10')->name('pdfQuestions.extract');
    Route::get('/admin/pdf-questions/{candidate}', [\App\Http\Controllers\Admin\PdfQuestionController::class, 'show'])->whereNumber('candidate')->name('pdfQuestions.show');
    Route::get('/admin/pdf-questions/{candidate}/page', [\App\Http\Controllers\Admin\PdfQuestionController::class, 'page'])->whereNumber('candidate')->middleware('throttle:60,1')->name('pdfQuestions.page');
    Route::post('/admin/learning-content/{resource}/questions/ocr', [\App\Http\Controllers\Admin\PdfQuestionController::class, 'ocr'])->whereNumber('resource')->middleware('throttle:6,10')->name('pdfQuestions.ocr');
    Route::post('/admin/pdf-questions/{candidate}/review', [\App\Http\Controllers\Admin\PdfQuestionController::class, 'review'])->whereNumber('candidate')->name('pdfQuestions.review');
    Route::get('/admin/learning-content/{resource}/extract', [\App\Http\Controllers\Admin\LearningContentController::class, 'extract'])->whereNumber('resource')->name('learningContent.extract');
    Route::post('/admin/learning-content/{resource}/signs', [\App\Http\Controllers\Admin\LearningContentController::class, 'storeSign'])->whereNumber('resource')->name('learningContent.storeSign');
    Route::post('/admin/learning-content/{resource}/vision', [\App\Http\Controllers\Admin\LearningContentController::class, 'storeVision'])->whereNumber('resource')->name('learningContent.storeVision');
    Route::post('/admin/learning-content/fetch', [\App\Http\Controllers\Admin\LearningContentController::class, 'fetch'])->middleware('throttle:4,10')->name('learningContent.fetch');
    Route::get('/admin/learning-content/{resource}', [\App\Http\Controllers\Admin\LearningContentController::class, 'show'])->whereNumber('resource')->name('learningContent.show');
    Route::post('/admin/learning-content/{resource}/download', [\App\Http\Controllers\Admin\LearningContentController::class, 'download'])->whereNumber('resource')->middleware('throttle:6,10')->name('learningContent.download');
    Route::get('/admin/learning-content/{resource}/file', [\App\Http\Controllers\Admin\LearningContentController::class, 'file'])->whereNumber('resource')->name('learningContent.file');
    Route::post('/admin/learning-content/{resource}/review', [\App\Http\Controllers\Admin\LearningContentController::class, 'review'])->whereNumber('resource')->name('learningContent.review');
    Route::get('/admin/search', [\App\Http\Controllers\Admin\SearchController::class, 'index'])->name('adminSearch');
    Route::get('admin', [DashboardController::class, 'index'])->name('adminDashboard');
Route::get('/admin/analytics', [DashboardController::class, 'analytics'])->name('adminAnalytics');
Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('auditLogs');
Route::get('/admin/settings', [AppSettingController::class, 'index'])->name('appSettings');
Route::patch('/admin/settings/{section}', [AppSettingController::class, 'update'])->whereIn('section', ['question-bank', 'verification', 'users', 'uploads', 'general'])->name('appSettings.update');

Route::get('/admin/profile-settings', [UserController::class, 'profileSettings'])->name('profileSettings');
Route::patch('/admin/profile-settings/name', [UserController::class, 'updateProfileName'])->name('profile.name.update');
Route::patch('/admin/profile-settings/email', [UserController::class, 'updateProfileEmail'])->name('profile.email.update');
Route::patch('/admin/profile-settings/phone', [UserController::class, 'updateProfilePhone'])->name('profile.phone.update');
Route::patch('/admin/profile-settings/password', [UserController::class, 'updateProfilePassword'])->name('profile.password.update');
Route::patch('/admin/profile-settings/image', [UserController::class, 'updateProfileImage'])->name('profile.image.update');
Route::post('/admin/profile-settings/email/verification-notification', [ProfileVerificationController::class, 'sendEmail'])
    ->middleware('throttle:6,1')->name('verification.send');
Route::get('/admin/profile-settings/email/verify/{id}/{hash}', [ProfileVerificationController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
Route::post('/admin/profile-settings/phone/verification-code', [ProfileVerificationController::class, 'sendPhone'])
    ->middleware(['throttle:3,10', 'throttle:phone-resend'])->name('profile.phone.verification.send');
Route::post('/admin/profile-settings/phone/verify', [ProfileVerificationController::class, 'verifyPhone'])
    ->middleware('throttle:6,1')->name('profile.phone.verification.verify');
Route::get('/admin/users', [UserController::class, 'allUser'])->name('allUser');
Route::get('/admin/users/{id}', [UserController::class, 'showUser'])->whereNumber('id')->name('showUser');
Route::get('/admin/add-user', [UserController::class, 'addUser'])->name('addUser');
Route::post('/admin/insert-user', [UserController::class, 'insertUser'])->name('insertUser');
Route::get('/admin/edit-user/{id}', [UserController::class, 'editUser'])->name('editUser');
Route::post('/admin/update-user/{id}', [UserController::class, 'updateUser'])->name('updateUser');
Route::delete('/admin/delete-user/{id}', [UserController::class, 'deleteUser'])->name('deleteUser');
Route::patch('/admin/users/{id}/suspend', [UserController::class, 'suspendUser'])->name('suspendUser');
Route::patch('/admin/users/{id}/reactivate', [UserController::class, 'reactivateUser'])->name('reactivateUser');
Route::delete('/admin/users/{id}/tokens', [UserController::class, 'revokeUserTokens'])->name('revokeUserTokens');

Route::get('/admin/exam-papers', [ExamPaperController::class, 'allExamPaper'])->name('allExamPaper');
Route::get('/admin/add-exam-paper', [ExamPaperController::class, 'addExamPaper'])->name('addExamPaper');
Route::post('/admin/insert-exam-paper', [ExamPaperController::class, 'insertExamPaper'])->name('insertExamPaper');
Route::get('/admin/edit-exam-paper/{id}', [ExamPaperController::class, 'editExamPaper'])->name('editExamPaper');
Route::post('/admin/update-exam-paper/{id}', [ExamPaperController::class, 'updateExamPaper'])->name('updateExamPaper');
Route::delete('/admin/delete-exam-paper/{id}', [ExamPaperController::class, 'deleteExamPaper'])->name('deleteExamPaper');

Route::get('/admin/traffic-signs', [TrafficSignController::class, 'allTrafficSign'])->name('allTrafficSign');
Route::get('/admin/add-traffic-sign', [TrafficSignController::class, 'addTrafficSign'])->name('addTrafficSign');
Route::post('/admin/insert-traffic-sign', [TrafficSignController::class, 'insertTrafficSign'])->name('insertTrafficSign');
Route::get('/admin/edit-traffic-sign/{id}', [TrafficSignController::class, 'editTrafficSign'])->name('editTrafficSign');
Route::post('/admin/update-traffic-sign/{id}', [TrafficSignController::class, 'updateTrafficSign'])->name('updateTrafficSign');
Route::delete('/admin/delete-traffic-sign/{id}', [TrafficSignController::class, 'deleteTrafficSign'])->name('deleteTrafficSign');

Route::get('/admin/vision-tests', [VisionTestController::class, 'allVisionTest'])->name('allVisionTest');
Route::get('/admin/add-vision-test', [VisionTestController::class, 'addVisionTest'])->name('addVisionTest');
Route::post('/admin/insert-vision-test', [VisionTestController::class, 'insertVisionTest'])->name('insertVisionTest');
Route::get('/admin/edit-vision-test/{id}', [VisionTestController::class, 'editVisionTest'])->name('editVisionTest');
Route::post('/admin/update-vision-test/{id}', [VisionTestController::class, 'updateVisionTest'])->name('updateVisionTest');
Route::delete('/admin/delete-vision-test/{id}', [VisionTestController::class, 'deleteVisionTest'])->name('deleteVisionTest');

Route::get('/admin/exam-information', [ExamInformationController::class, 'allExamInformation'])->name('allExamInformation');
Route::get('/admin/add-exam-information', [ExamInformationController::class, 'addExamInformation'])->name('addExamInformation');
Route::post('/admin/insert-exam-information', [ExamInformationController::class, 'insertExamInformation'])->name('insertExamInformation');
Route::get('/admin/edit-exam-information/{id}', [ExamInformationController::class, 'editExamInformation'])->name('editExamInformation');
Route::post('/admin/update-exam-information/{id}', [ExamInformationController::class, 'updateExamInformation'])->name('updateExamInformation');
Route::delete('/admin/delete-exam-information/{id}', [ExamInformationController::class, 'deleteExamInformation'])->name('deleteExamInformation');

Route::get('/admin/questions', [QuestionController::class, 'allQuestion'])->name('allQuestion');
Route::get('/admin/questions-trash', [QuestionController::class, 'trashedQuestions'])->name('questionTrash');
Route::get('/admin/add-question', [QuestionController::class, 'addQuestion'])->name('addQuestion');
Route::post('/admin/insert-question', [QuestionController::class, 'insertQuestion'])->name('insertQuestion');
Route::get('/admin/edit-question/{id}', [QuestionController::class, 'editQuestion'])->name('editQuestion');
Route::get('/admin/questions/{id}/preview', [QuestionController::class, 'previewQuestion'])->whereNumber('id')->name('previewQuestion');
Route::post('/admin/update-question/{id}', [QuestionController::class, 'updateQuestion'])->name('updateQuestion');
Route::delete('/admin/delete-question/{id}', [QuestionController::class, 'deleteQuestion'])->name('deleteQuestion');
Route::get('/admin/questions-import', [QuestionImportController::class, 'create'])->name('questionImport');
Route::post('/admin/questions-import', [QuestionImportController::class, 'store'])->name('questionImport.store');
Route::get('/admin/questions-import/template', [QuestionImportController::class, 'template'])->name('questionImport.template');
Route::get('/admin/questions-export', [QuestionImportController::class, 'export'])->name('questionExport');
Route::patch('/admin/questions-trash/{id}/restore', [QuestionController::class, 'restoreQuestion'])->name('restoreQuestion');
Route::delete('/admin/questions-trash/{id}', [QuestionController::class, 'forceDeleteQuestion'])->name('forceDeleteQuestion');

Route::get('/admin/tutorials', [TutorialController::class, 'allTutorial'])->name('allTutorial');
Route::get('/admin/add-tutorial', [TutorialController::class, 'addTutorial'])->name('addTutorial');
Route::post('/admin/insert-tutorial', [TutorialController::class, 'insertTutorial'])->name('insertTutorial');
Route::get('/admin/edit-tutorial/{id}', [TutorialController::class, 'editTutorial'])->name('editTutorial');
Route::post('/admin/update-tutorial/{id}', [TutorialController::class, 'updateTutorial'])->name('updateTutorial');
Route::delete('/admin/delete-tutorial/{id}', [TutorialController::class, 'deleteTutorial'])->name('deleteTutorial');

Route::get('/admin/notices', [NoticeController::class, 'allNotice'])->name('allNotice');
Route::get('/admin/notices-import', [NoticeImportController::class, 'create'])->name('noticeImport');
Route::post('/admin/notices-import', [NoticeImportController::class, 'store'])->name('noticeImport.store');
Route::get('/admin/notices-import/template', [NoticeImportController::class, 'template'])->name('noticeImport.template');
Route::get('/admin/notices-export', [NoticeImportController::class, 'export'])->name('noticeExport');
Route::patch('/admin/notices/{id}/archive', [NoticeController::class, 'archiveNotice'])->name('noticeArchive');
Route::patch('/admin/notices/{id}/unarchive', [NoticeController::class, 'unarchiveNotice'])->name('noticeUnarchive');
Route::get('/admin/notices-trash', [NoticeController::class, 'trashedNotices'])->name('noticeTrash');
Route::get('/admin/government-notices', [GovernmentNoticeImportController::class, 'index'])->name('governmentNotices');
Route::post('/admin/government-notices/fetch', [GovernmentNoticeImportController::class, 'fetch'])->middleware('throttle:2,10')->name('governmentNotices.fetch');
Route::post('/admin/government-notices/{id}/approve', [GovernmentNoticeImportController::class, 'approve'])->name('governmentNotices.approve');
Route::post('/admin/government-notices/{id}/reject', [GovernmentNoticeImportController::class, 'reject'])->name('governmentNotices.reject');
Route::get('/admin/add-notice', [NoticeController::class, 'addNotice'])->name('addNotice');
Route::post('/admin/insert-notice', [NoticeController::class, 'insertNotice'])->name('insertNotice');
Route::get('/admin/edit-notice/{id}', [NoticeController::class, 'editNotice'])->name('editNotice');
Route::post('/admin/update-notice/{id}', [NoticeController::class, 'updateNotice'])->name('updateNotice');
Route::delete('/admin/delete-notice/{id}', [NoticeController::class, 'deleteNotice'])->name('deleteNotice');
Route::patch('/admin/notices-trash/{id}/restore', [NoticeController::class, 'restoreNotice'])->name('restoreNotice');
Route::delete('/admin/notices-trash/{id}', [NoticeController::class, 'forceDeleteNotice'])->name('forceDeleteNotice');
});
