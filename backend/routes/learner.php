<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Learner\{LibraryController,AuthController,PracticeController,SupportController};
Route::get('/', [LibraryController::class,'home'])->name('learn.home');
Route::prefix('learn')->name('learn.')->group(function () {
    Route::post('language', [\App\Http\Controllers\Learner\LanguageController::class, 'update'])->name('language');
    Route::get('premium',[\App\Http\Controllers\Learner\PremiumController::class,'index'])->name('premium');
    Route::get('auth/google',[\App\Http\Controllers\Learner\GoogleLoginController::class,'redirect'])->middleware('throttle:10,1,google')->name('google');
    Route::get('auth/google/callback',[\App\Http\Controllers\Learner\GoogleLoginController::class,'callback'])->middleware('throttle:10,1,google')->name('google.callback');
    Route::get('auth/google/finish',[\App\Http\Controllers\Learner\GoogleLoginController::class,'finishForm'])->name('google.finish');
    Route::post('auth/google/finish',[\App\Http\Controllers\Learner\GoogleLoginController::class,'finish'])->middleware('throttle:5,1,google-signup');
    Route::get('forgot-password',[\App\Http\Controllers\Learner\EmailSecurityController::class,'forgot'])->name('password.request');
    Route::post('forgot-password',[\App\Http\Controllers\Learner\EmailSecurityController::class,'email'])->middleware('throttle:3,1,learner-recovery')->name('password.email');
    Route::get('reset-password/{token}',[\App\Http\Controllers\Learner\EmailSecurityController::class,'resetForm'])->name('password.reset');
    Route::post('reset-password',[\App\Http\Controllers\Learner\EmailSecurityController::class,'reset'])->middleware('throttle:5,1,learner-reset')->name('password.update');
    Route::view('library','learner.library-hub')->name('library.home');
    Route::get('sign-flashcards',[LibraryController::class,'flashcards'])->name('flashcards');
    Route::get('library/{type}',[LibraryController::class,'index'])->name('library');
    Route::get('library/{type}/{id}',[LibraryController::class,'show'])->whereNumber('id')->name('detail');
    Route::get('practice',[PracticeController::class,'index'])->name('practice');
    Route::get('login',[AuthController::class,'loginForm'])->name('login');
    Route::post('login',[AuthController::class,'login'])->middleware('throttle:5,1');
    Route::get('register',[AuthController::class,'registerForm'])->name('register');
    Route::post('register',[AuthController::class,'register'])->middleware('throttle:5,1');
    Route::post('logout',[AuthController::class,'logout'])->name('logout');
    Route::middleware(\App\Http\Middleware\LearnerSession::class)->group(function () {
        Route::get('premium/payments',[\App\Http\Controllers\Learner\PaymentController::class,'index'])->name('payments');
        Route::post('premium/checkout',[\App\Http\Controllers\Learner\PaymentController::class,'checkout'])->middleware('throttle:3,1,premium-payment')->name('payments.checkout');
        Route::get('premium/payments/{id}',[\App\Http\Controllers\Learner\PaymentController::class,'show'])->name('payments.show');
        Route::get('premium/payments/{id}/return',[\App\Http\Controllers\Learner\PaymentController::class,'verify'])->middleware('throttle:6,1,payment-check')->name('payments.return');
        Route::post('premium/payments/{id}/verify',[\App\Http\Controllers\Learner\PaymentController::class,'verify'])->middleware('throttle:6,1,payment-check')->name('payments.verify');
        Route::post('premium/request',[\App\Http\Controllers\Learner\PremiumController::class,'requestUpgrade'])->middleware('throttle:3,1,premium-upgrade')->name('premium.request');
        Route::middleware(\App\Http\Middleware\RequirePremium::class)->group(function(){
            Route::get('premium/revision',[\App\Http\Controllers\Learner\PremiumController::class,'modules'])->name('premium.modules');
            Route::get('premium/chat',[\App\Http\Controllers\Learner\PremiumController::class,'chat'])->name('premium.chat');
            Route::post('premium/chat',[\App\Http\Controllers\Learner\PremiumController::class,'message'])->middleware('throttle:5,1,premium-chat')->name('premium.message');
        });
        Route::get('mfa/challenge',[\App\Http\Controllers\Learner\MfaController::class,'challenge'])->name('mfa.challenge');
        Route::post('mfa/challenge',[\App\Http\Controllers\Learner\MfaController::class,'verify'])->middleware('throttle:5,1,mfa')->name('mfa.verify');
        Route::post('mfa/setup',[\App\Http\Controllers\Learner\MfaController::class,'setup'])->middleware('throttle:5,1,mfa-setup')->name('mfa.setup');
        Route::post('mfa/enable',[\App\Http\Controllers\Learner\MfaController::class,'enable'])->middleware('throttle:5,1,mfa-setup')->name('mfa.enable');
        Route::delete('mfa',[\App\Http\Controllers\Learner\MfaController::class,'disable'])->middleware('throttle:5,1,mfa-disable')->name('mfa.disable');
        Route::post('auth/google/connect',[\App\Http\Controllers\Learner\GoogleLoginController::class,'connect'])->middleware('throttle:5,1,google-connect')->name('google.connect');
        Route::delete('auth/google',[\App\Http\Controllers\Learner\GoogleLoginController::class,'disconnect'])->middleware('throttle:5,1,google-connect')->name('google.disconnect');
        Route::patch('settings/detail/{field}',[\App\Http\Controllers\Learner\SettingsController::class,'detail'])->middleware('throttle:10,1')->name('settings.detail');
        Route::post('verify-email',[\App\Http\Controllers\Learner\EmailSecurityController::class,'send'])->middleware('throttle:3,1')->name('verification.send');
        Route::get('verify-email/{id}/{hash}',[\App\Http\Controllers\Learner\EmailSecurityController::class,'verify'])->middleware(['signed','throttle:6,1'])->name('verification.verify');
        Route::get('settings',[\App\Http\Controllers\Learner\SettingsController::class,'edit'])->name('settings');
        Route::patch('settings/profile',[\App\Http\Controllers\Learner\SettingsController::class,'profile'])->middleware('throttle:10,1')->name('settings.profile');
        Route::put('settings/password',[\App\Http\Controllers\Learner\SettingsController::class,'password'])->middleware('throttle:5,1')->name('settings.password');
        Route::get('account',[LibraryController::class,'account'])->name('account');
        Route::get('saved',[\App\Http\Controllers\Learner\BookmarkController::class,'index'])->name('saved');
        Route::post('saved/{type}/{id}',[\App\Http\Controllers\Learner\BookmarkController::class,'store'])->whereNumber('id')->middleware('throttle:30,1')->name('saved.store');
        Route::delete('saved/{id}',[\App\Http\Controllers\Learner\BookmarkController::class,'destroy'])->whereNumber('id')->name('saved.destroy');
        Route::patch('practice/{id}/draft',[PracticeController::class,'saveDraft'])->whereNumber('id')->middleware('throttle:60,1')->name('practice.draft');
        Route::post('practice/start',[PracticeController::class,'start'])->middleware('throttle:10,1')->name('practice.start');
        Route::get('practice/{id}',[PracticeController::class,'show'])->whereNumber('id')->name('practice.attempt');
        Route::post('practice/{id}',[PracticeController::class,'submit'])->whereNumber('id');
        Route::get('support',[SupportController::class,'index'])->name('support');
        Route::post('support',[SupportController::class,'store'])->middleware('throttle:10,10');
        Route::get('support/{id}',[SupportController::class,'show'])->whereNumber('id')->name('support.show');
        Route::post('support/{id}',[SupportController::class,'reply'])->whereNumber('id')->middleware('throttle:10,10');
        Route::get('report/{type}/{id}',[SupportController::class,'reportForm'])->whereNumber('id')->name('report');
        Route::post('report/{type}/{id}',[SupportController::class,'report'])->whereNumber('id')->middleware('throttle:10,10');
    });
});

Route::get('learn/mobile/connect/{id}',[\App\Http\Controllers\API\MobileBrowserController::class,'connect'])->name('learn.mobile.connect')->middleware('throttle:10,1');
Route::post('learn/mobile/connect/{id}',[\App\Http\Controllers\API\MobileBrowserController::class,'approve'])->name('learn.mobile.approve')->middleware(['auth','throttle:5,1']);
Route::get('learn/mobile/handoff/{id}',[\App\Http\Controllers\API\MobileBrowserController::class,'handoffForm'])->name('learn.mobile.handoff')->middleware('throttle:10,1');
Route::post('learn/mobile/handoff/{id}',[\App\Http\Controllers\API\MobileBrowserController::class,'accept'])->name('learn.mobile.accept')->middleware('throttle:5,1');

Route::get('learn/practice-archive',function(\Illuminate\Http\Request $request){
    return view('learner.practice-archive',['records'=>\App\Models\UserHistory::where('user_id',$request->user()->id)->latest('id')->paginate(20)]);
})->middleware(\App\Http\Middleware\LearnerSession::class)->name('learn.practice.archive');
