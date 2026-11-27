<?php
namespace App\Http\Controllers\Learner;
use App\Support\LearnerIntendedRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,Hash};
class AuthController extends \App\Http\Controllers\Controller {
    private function dashboard(){
        if (auth()->user()->role === 'Admin') return redirect()->route('adminDashboard');
        if (auth()->user()->schoolMembershipsForStaff()->exists()) return redirect()->route('learn.school.dashboard');
        return redirect()->route('learn.account');
    }
    public function loginForm(Request $request){
        // A "sign in to continue" link (e.g. from the lessons or
        // private-lessons page) passes its destination as ?redirect=;
        // remember it with the normal intended-URL mechanism so sign-in
        // brings the user back there.
        LearnerIntendedRedirect::store($request, $request->query('redirect'));
        // The sign-in page explains itself: a guest bounced from support,
        // a report form, a course, or a private session (auth middleware
        // stored url.intended) sees why sign-in is needed there instead of
        // the generic line. Read the stored destination without pulling
        // it — login() still needs it.
        $subtitle = LearnerIntendedRedirect::subtitleFor($request->session()->get('url.intended'));
        return auth()->check()?$this->dashboard():view('learner.auth',['register'=>false, 'subtitle' => $subtitle]);
    }
    public function registerForm(Request $request){
        LearnerIntendedRedirect::store($request, $request->query('redirect'));
        return auth()->check()?$this->dashboard():view('learner.auth',['register'=>true]);
    }
    public function login(Request $request){
        $data=$request->validate(['email'=>['required','email'],'password'=>['required','string']]);
        if(!Auth::attempt($data+['is_active'=>true])) return back()->withErrors(['email'=>__('The email or password is incorrect, or this account is unavailable.')])->onlyInput('email');
        $request->session()->regenerate();$request->user()->update(['last_login_at'=>now()]);
        $request->session()->forget('mfa_verified');
        if($request->user()->mfa_secret)return redirect()->route('learn.mfa.challenge');
        if($intended=LearnerIntendedRedirect::pull($request))return redirect()->to($intended);
        return $this->dashboard();
    }
    public function register(Request $request){
        $data=$request->validate(['name'=>['required','string','max:100'],'email'=>['required','email','max:255','unique:users,email'],
            'phoneNumber'=>['required','digits:10'],'password'=>['required','confirmed',\Illuminate\Validation\Rules\Password::min(8)],'terms'=>['accepted'],'age'=>['accepted']]);
        $user=\App\Models\User::create(['name'=>$data['name'],'email'=>$data['email'],'phoneNumber'=>$data['phoneNumber'],'password'=>Hash::make($data['password']),'role'=>'User','profileImage'=>'dist/img/avatar.png','is_active'=>true]);
        Auth::login($user);$request->session()->regenerate();
        if($intended=LearnerIntendedRedirect::pull($request))return redirect()->to($intended);
        // A `premium` flag (e.g. from the Premium page) offers Premium once
        // on the home page instead of sending the new user back to Premium.
        if($request->boolean('premium')) return redirect()->route('learn.home')->with('premium_offer',true)->with('success',__('Your account is ready. You can now upgrade to Premium.'));
        return redirect()->route('learn.account')->with('success',__('Your account is ready. Start with a short practice session.'));
    }
    public function logout(Request $request){Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect()->route('learn.home');}
}
