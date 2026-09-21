<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\QuestionController;
use App\Http\Controllers\API\TrafficSignController;
use App\Http\Controllers\API\ExamPaperController;
use App\Http\Controllers\API\VisionTestController;
use App\Http\Controllers\API\ExamInformationController;
use App\Http\Controllers\API\TutorialController;
use App\Http\Controllers\API\NoticeController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserHistoryController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\AppConfigurationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:api-register');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:api-login');

Route::get('/trafficSign', [TrafficSignController::class, 'index']);
Route::get('/examPaper', [ExamPaperController::class, 'index']);
Route::get('/visionTest', [VisionTestController::class, 'index']);
Route::get('/examInformation', [ExamInformationController::class, 'index']);
Route::get('/question', [QuestionController::class, 'index']);
Route::get('/tutorial', [TutorialController::class, 'index']);
Route::get('/notice', [NoticeController::class, 'index']);
Route::get('/configuration', [AppConfigurationController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/support-tickets', [\App\Http\Controllers\API\SupportController::class, 'index']);
    Route::post('/support-tickets', [\App\Http\Controllers\API\SupportController::class, 'store'])->middleware('throttle:5,10');
    Route::get('/support-tickets/{ticket}', [\App\Http\Controllers\API\SupportController::class, 'show'])->whereNumber('ticket');
    Route::post('/support-tickets/{ticket}/replies', [\App\Http\Controllers\API\SupportController::class, 'reply'])->whereNumber('ticket')->middleware('throttle:10,10');
    Route::post('/content/{type}/{id}/report', [\App\Http\Controllers\API\SupportController::class, 'report'])->whereNumber('id')->middleware('throttle:10,10');
    Route::get('/user', fn (Request $request) => $request->user());
    Route::put('/auth/password', [AuthController::class, 'changePassword'])->middleware('throttle:api-password');
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/userHistory', [UserHistoryController::class, 'recordHistory']);
    Route::get('/userHistory', [UserHistoryController::class, 'displayHistory']);
    Route::post('/payment', [PaymentController::class, 'payment']);
});


Route::prefix('learner')->middleware(\App\Http\Middleware\MobileLearner::class)->group(function () {
    $controller = \App\Http\Controllers\API\LearnerController::class;
    $practice = \App\Http\Controllers\Learner\PracticeController::class;
    Route::get('catalog', [$controller,'catalog']);
    Route::get('library/{type}', [$controller,'library']);
    Route::get('library/{type}/{id}', [$controller,'detail'])->whereNumber('id');
    Route::middleware(['auth:sanctum', \App\Http\Middleware\MobileLearner::class])->group(function () use ($controller,$practice) {
        Route::get('account', [$controller,'account']);
        $account=\App\Http\Controllers\API\LearnerAccountController::class;
        Route::patch('settings/{field}',[$account,'detail'])->middleware('throttle:10,1');
        Route::post('verify-email',[$account,'verification'])->middleware('throttle:3,1');
        Route::post('mfa/setup',[$account,'setup'])->middleware('throttle:5,1');
        Route::post('mfa/enable',[$account,'enable'])->middleware('throttle:5,1');
        Route::delete('mfa',[$account,'disable'])->middleware('throttle:5,1');

        Route::get('saved', [$controller,'saved']);
        Route::post('saved/{type}/{id}', [$controller,'save'])->whereNumber('id')->middleware('throttle:30,1');
        Route::delete('saved/{id}', [$controller,'unsave'])->whereNumber('id');
        Route::post('practice', [$practice,'start'])->middleware('throttle:10,1');
        Route::get('practice/{id}', [$practice,'show'])->whereNumber('id');
        Route::patch('practice/{id}', [$practice,'saveDraft'])->whereNumber('id');
        Route::post('practice/{id}', [$practice,'submit'])->whereNumber('id');
        Route::post('premium/request',[\App\Http\Controllers\Learner\PremiumController::class,'requestUpgrade'])->middleware('throttle:3,1');
        Route::get('payments', [$controller,'payments']);
        Route::post('payments/{id}/verify', [$controller,'verifyPayment'])->middleware('throttle:6,1');
        Route::get('revision', [$controller,'revision']);
        Route::get('chat', [$controller,'chat']);
        Route::post('chat', function (\Illuminate\Http\Request $request) {
            abort_unless($request->user()->hasPremium(),403);
            return app(\App\Http\Controllers\Learner\PremiumController::class)->message($request,app(\App\Services\LearnerChat::class),app(\App\Services\PracticeRevision::class));
        })->middleware('throttle:5,1');
    });
});

Route::post('auth/forgot-password', [\App\Http\Controllers\API\LearnerAccountController::class,'forgot'])->middleware('throttle:3,1');

Route::post('mobile/login/start',[\App\Http\Controllers\API\MobileBrowserController::class,'start'])->middleware('throttle:5,1');
Route::post('mobile/login/exchange',[\App\Http\Controllers\API\MobileBrowserController::class,'exchange'])->middleware('throttle:10,1');
Route::post('mobile/handoff',[\App\Http\Controllers\API\MobileBrowserController::class,'handoff'])->middleware(['auth:sanctum',\App\Http\Middleware\MobileLearner::class,'throttle:5,1']);
