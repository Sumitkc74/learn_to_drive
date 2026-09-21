<?php
namespace App\Http\Controllers\Learner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,Hash};
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
class GoogleLoginController extends \App\Http\Controllers\Controller {
    private function available() { abort_unless(config('services.google.client_id') && config('services.google.client_secret') && config('services.google.redirect'),503,__('Google sign-in is not configured yet.')); }
    public function redirect(Request $request) {
        $this->available();
        if($request->user())return redirect()->route('learn.settings');
        $request->session()->put('google_flow',['expires'=>time()+600,'link'=>null]);
        return Socialite::driver('google')->redirect();
    }
    public function connect(Request $request) {
        $this->available();$request->validate(['current_password'=>['required','current_password:web']]);
        $request->session()->put('google_flow',['expires'=>time()+600,'link'=>$request->user()->id]);
        return Socialite::driver('google')->redirect();
    }
    public function callback(Request $request) {
        $this->available();$flow=$request->session()->pull('google_flow');
        if(!$flow || $flow['expires']<time())return redirect()->route('learn.login')->with('error',__('Google sign-in expired. Please start again.'));
        try { $google=Socialite::driver('google')->user(); }
        catch(\Throwable $e) { return redirect()->route('learn.login')->with('error',__('Google sign-in was cancelled or could not be completed. Please try again.')); }
        if(empty($google->user['email_verified']) || !$google->getId() || !filter_var($google->getEmail(),FILTER_VALIDATE_EMAIL))return redirect()->route('learn.login')->with('error',__('Google must provide a verified email address.'));
        $linked=User::where('google_id',$google->getId())->first();
        if($flow['link']) {
            abort_unless($request->user()?->id===$flow['link'] && $request->user()->is_active,403);
            if($linked && $linked->id!==$flow['link'])return redirect()->route('learn.settings')->with('error',__('That Google account is already connected to another account.'));
            $request->user()->forceFill(['google_id'=>$google->getId()])->save();
            return redirect()->route('learn.settings')->with('success',__('Google account connected.'));
        }
        if($request->user())return redirect()->route('learn.settings');
        if($linked) {
            if(!$linked->is_active)return redirect()->route('learn.login')->with('error',__('This account is unavailable.'));
            Auth::login($linked);$request->session()->regenerate();$request->session()->forget('mfa_verified');
            $linked->update(['last_login_at'=>now()]);
            return redirect()->route($linked->mfa_secret?'learn.mfa.challenge':($linked->role==='Admin'?'adminDashboard':'learn.account'));
        }
        if(User::where('email',$google->getEmail())->exists())return redirect()->route('learn.login')->with('error',__('An account already uses this email. Sign in with your password, then connect Google in Account settings.'));
        $request->session()->put('google_signup',['id'=>$google->getId(),'email'=>$google->getEmail(),'name'=>Str::limit($google->getName() ?: 'Learner',100,''),'expires'=>time()+600]);
        return redirect()->route('learn.google.finish');
    }
    public function finishForm(Request $request) {
        $data=$request->session()->get('google_signup');abort_unless($data && $data['expires']>=time(),419);
        return response()->view('learner.google-finish',['google'=>$data])->header('Cache-Control','private, no-store');
    }
    public function finish(Request $request) {
        abort_if((bool)$request->user(),409);
        $google=$request->session()->get('google_signup');abort_unless($google && $google['expires']>=time(),419);
        $data=$request->validate(['name'=>'required|string|max:100','phoneNumber'=>'required|digits:10','password'=>['required','confirmed',\Illuminate\Validation\Rules\Password::min(8)]]);
        if(User::where('email',$google['email'])->orWhere('google_id',$google['id'])->exists())return redirect()->route('learn.login')->with('error',__('This account already exists. Sign in to continue.'));
        $user=new User($data+['email'=>$google['email'],'role'=>'User','is_active'=>true,'profileImage'=>'dist/img/avatar.png']);
        $user->password=Hash::make($data['password']);$user->forceFill(['google_id'=>$google['id'],'email_verified_at'=>now()])->save();
        $request->session()->forget('google_signup');Auth::login($user);$request->session()->regenerate();
        return redirect()->route('learn.account')->with('success',__('Your account is ready. You can sign in with Google or your password.'));
    }
    public function disconnect(Request $request) {
        $request->validate(['current_password'=>['required','current_password:web']]);
        $request->user()->forceFill(['google_id'=>null])->save();
        return back()->with('success',__('Google disconnected. Use your email and password to sign in.'));
    }
}
