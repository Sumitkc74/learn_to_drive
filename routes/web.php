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
use App\Http\Controllers\Admin\ProfileVerificationController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AppSettingController;
use App\Http\Controllers\Admin\QuestionImportController;
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

Route::get('/', function () {
    return view('welcome');
});

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
    Route::get('admin', [DashboardController::class, 'index'])->name('adminDashboard');
Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('auditLogs');
Route::get('/admin/settings', [AppSettingController::class, 'index'])->name('appSettings');
Route::patch('/admin/settings', [AppSettingController::class, 'update'])->name('appSettings.update');

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
    ->middleware('throttle:3,10')->name('profile.phone.verification.send');
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
Route::get('/admin/notices-trash', [NoticeController::class, 'trashedNotices'])->name('noticeTrash');
Route::get('/admin/add-notice', [NoticeController::class, 'addNotice'])->name('addNotice');
Route::post('/admin/insert-notice', [NoticeController::class, 'insertNotice'])->name('insertNotice');
Route::get('/admin/edit-notice/{id}', [NoticeController::class, 'editNotice'])->name('editNotice');
Route::post('/admin/update-notice/{id}', [NoticeController::class, 'updateNotice'])->name('updateNotice');
Route::delete('/admin/delete-notice/{id}', [NoticeController::class, 'deleteNotice'])->name('deleteNotice');
Route::patch('/admin/notices-trash/{id}/restore', [NoticeController::class, 'restoreNotice'])->name('restoreNotice');
Route::delete('/admin/notices-trash/{id}', [NoticeController::class, 'forceDeleteNotice'])->name('forceDeleteNotice');
});
