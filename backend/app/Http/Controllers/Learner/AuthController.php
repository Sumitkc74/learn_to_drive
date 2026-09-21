<?php
namespace App\Http\Controllers\Learner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,Hash};
class AuthController extends \App\Http\Controllers\Controller {
    private function dashboard(){return redirect()->route(auth()->user()->role==='Admin'?'adminDashboard':'learn.account');}
    public function loginForm(){return auth()->check()?$this->dashboard():view('learner.auth',['register'=>false]);}
    public function registerForm(){return auth()->check()?$this->dashboard():view('learner.auth',['register'=>true]);}
    public function login(Request $request){
        $data=$request->validate(['email'=>['required','email'],'password'=>['required','string']]);
        if(!Auth::attempt($data+['is_active'=>true])) return back()->withErrors(['email'=>__('The email or password is incorrect, or this account is unavailable.')])->onlyInput('email');
        $request->session()->regenerate();$request->user()->update(['last_login_at'=>now()]);
        $request->session()->forget('mfa_verified');
        if($request->user()->mfa_secret)return redirect()->route('learn.mfa.challenge');
        $intended=$request->session()->pull('url.intended');
        if(is_string($intended) && str_starts_with($intended,url('/learn/verify-email/'))) return redirect()->to($intended);
        return $this->dashboard();
    }
    public function register(Request $request){
        $data=$request->validate(['name'=>['required','string','max:100'],'email'=>['required','email','max:255','unique:users,email'],
            'phoneNumber'=>['required','digits:10'],'password'=>['required','confirmed',\Illuminate\Validation\Rules\Password::min(8)]]);
        $user=\App\Models\User::create(['name'=>$data['name'],'email'=>$data['email'],'phoneNumber'=>$data['phoneNumber'],'password'=>Hash::make($data['password']),'role'=>'User','profileImage'=>'dist/img/avatar.png','is_active'=>true]);
        Auth::login($user);$request->session()->regenerate();return redirect()->route('learn.account')->with('success',__('Your account is ready. Start with a short practice session.'));
    }
    public function logout(Request $request){Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect()->route('learn.home');}
}
