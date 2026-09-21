<?php
namespace App\Http\Controllers\Learner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Password,Hash};
use Illuminate\Support\Str;
class EmailSecurityController extends \App\Http\Controllers\Controller
{
    public function send(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) return back()->with('success',__('Your email is already verified.'));
        try { $request->user()->notify(new \App\Notifications\LearnerVerifyEmail); }
        catch (\Throwable $e) { report($e); return back()->with('error',__('We could not send the verification email. Please try again later.')); }
        return back()->with('success',__('Verification email sent. Check your inbox and spam folder. The link expires in 60 minutes.'));
    }
    public function verify(Request $request, string $id, string $hash)
    {
        $user=$request->user();
        abort_unless((string)$user->id===$id && hash_equals(sha1($user->getEmailForVerification()),$hash),403);
        if (!$user->hasVerifiedEmail() && $user->markEmailAsVerified()) event(new \Illuminate\Auth\Events\Verified($user));
        return redirect()->route('learn.settings')->with('success',__('Your email is now verified.'));
    }
    public function forgot() { return view('learner.password', ['reset'=>false]); }
    public function resetForm(Request $request,string $token) { return view('learner.password',['reset'=>true,'token'=>$token,'email'=>$request->query('email')]); }
    public function email(Request $request)
    {
        $data=$request->validate(['email'=>'required|email|max:255']);
        try {
            Password::broker()->sendResetLink($data+['is_active'=>true], function ($user,$token) {
                $user->notify(new \App\Notifications\LearnerResetPassword($token));
            });
        } catch (\Throwable $e) { report($e); }
        return back()->with('success',__('If an active account matches that email, a password reset link has been sent. Check your inbox and spam folder.'));
    }
    public function reset(Request $request)
    {
        $data=$request->validate(['token'=>'required|string','email'=>'required|email','password'=>['required','confirmed',\Illuminate\Validation\Rules\Password::min(8)]]);
        $status=Password::reset($data+['is_active'=>true],function($user,$password){
            $user->forceFill(['password'=>Hash::make($password),'remember_token'=>Str::random(60)])->save();
            $user->tokens()->delete();
            event(new \Illuminate\Auth\Events\PasswordReset($user));
        });
        return $status===Password::PASSWORD_RESET
            ? redirect()->route('learn.login')->with('success',__('Password reset. You can now sign in with your new password.'))
            : back()->withErrors(['email'=>__($status)])->withInput($request->only('email'));
    }
}
