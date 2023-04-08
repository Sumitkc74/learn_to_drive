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
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserHistoryController;
use App\Http\Controllers\API\PaymentController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



Route::post('/auth/changePassword', [AuthController::class, 'changePassword']);
Route::post('/auth/resetPassword', [AuthController::class, 'resetPassword']);
Route::post('/auth/logout', [AuthController::class, 'logout']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/userHistory', [UserHistoryController::class, 'recordHistory']);
Route::post('/payment', [PaymentController::class, 'payment']);

// Route::group(['prefix' => '', 'middleware' => 'auth:api'], function () {
    Route::get('/trafficSign', [TrafficSignController::class, 'index']);
    Route::get('/examPaper', [ExamPaperController::class, 'index']);
    Route::get('/visionTest', [VisionTestController::class, 'index']);
    Route::get('/examInformation', [ExamInformationController::class, 'index']);
    Route::get('/question', [QuestionController::class, 'index']);
    Route::get('/tutorial', [TutorialController::class, 'index']);
    Route::get('/notice', [NoticeController::class, 'index']);
    Route::get('/user', [UserController::class, 'index']);
    Route::get('/userHistory', [UserHistoryController::class, 'displayHistory']);
// });
