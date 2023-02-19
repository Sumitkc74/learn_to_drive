<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ExamPaperController;
use App\Http\Controllers\Admin\TrafficSignController;
use App\Http\Controllers\Admin\VisionTestController;

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

Route::view('admin','admin.dashboard');
// Route::view('admin/users','admin.users.showUser');
Route::view('admin/traffic-signs','admin.addTrafficSigns');
// Route::view('admin/exam-papers','admin.addExamPapers');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/admin/users', [UserController::class, 'allUser'])->name('allUser');
Route::get('/admin/add-user', [UserController::class, 'addUser'])->name('addUser');
Route::post('/admin/insert-user', [UserController::class, 'insertUser'])->name('insertUser');
Route::get('/admin/edit-user/{id}', [UserController::class, 'editUser'])->name('editUser');
Route::post('/admin/update-user/{id}', [UserController::class, 'updateUser'])->name('updateUser');
Route::get('/admin/delete-user/{id}', [UserController::class, 'deleteUser'])->name('deleteUser');

Route::get('/admin/exam-papers', [ExamPaperController::class, 'allExamPaper'])->name('allExamPaper');
Route::get('/admin/add-exam-paper', [ExamPaperController::class, 'addExamPaper'])->name('addExamPaper');
Route::post('/admin/insert-exam-paper', [ExamPaperController::class, 'insertExamPaper'])->name('insertExamPaper');
Route::get('/admin/edit-exam-paper/{id}', [ExamPaperController::class, 'editExamPaper'])->name('editExamPaper');
Route::post('/admin/update-exam-paper/{id}', [ExamPaperController::class, 'updateExamPaper'])->name('updateExamPaper');
Route::get('/admin/delete-exam-paper/{id}', [ExamPaperController::class, 'deleteExamPaper'])->name('deleteExamPaper');

Route::get('/admin/traffic-signs', [TrafficSignController::class, 'allTrafficSign'])->name('allTrafficSign');
Route::get('/admin/add-traffic-sign', [TrafficSignController::class, 'addTrafficSign'])->name('addTrafficSign');
Route::post('/admin/insert-traffic-sign', [TrafficSignController::class, 'insertTrafficSign'])->name('insertTrafficSign');
Route::get('/admin/edit-traffic-sign/{id}', [TrafficSignController::class, 'editTrafficSign'])->name('editTrafficSign');
Route::post('/admin/update-traffic-sign/{id}', [TrafficSignController::class, 'updateTrafficSign'])->name('updateTrafficSign');
Route::get('/admin/delete-traffic-sign/{id}', [TrafficSignController::class, 'deleteTrafficSign'])->name('deleteTrafficSign');

Route::get('/admin/vision-tests', [VisionTestController::class, 'allVisionTest'])->name('allVisionTest');
Route::get('/admin/add-vision-test', [VisionTestController::class, 'addVisionTest'])->name('addVisionTest');
Route::post('/admin/insert-vision-test', [VisionTestController::class, 'insertVisionTest'])->name('insertVisionTest');
Route::get('/admin/edit-vision-test/{id}', [VisionTestController::class, 'editVisionTest'])->name('editVisionTest');
Route::post('/admin/update-vision-test/{id}', [VisionTestController::class, 'updateVisionTest'])->name('updateVisionTest');
Route::get('/admin/delete-vision-test/{id}', [VisionTestController::class, 'deleteVisionTest'])->name('deleteVisionTest');
