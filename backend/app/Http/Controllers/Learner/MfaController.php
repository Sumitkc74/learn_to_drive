<?php
namespace App\Http\Controllers\Learner;
use Illuminate\Http\Request;
use App\Services\AccountMfa;
use PragmaRX\Google2FA\Google2FA;
class MfaController extends \App\Http\Controllers\Controller {
    public function setup(Request $request) {
        $request->validate(['current_password'=>['required','current_password:web']]);
        abort_if((bool)$request->user()->mfa_secret,409);
        $request->session()->put('mfa_setup',['secret'=>(new Google2FA)->generateSecretKey(),'expires'=>time()+600,'user'=>$request->user()->id]);
        return redirect()->route('learn.settings')->with('success',__('Add the setup key to your authenticator, then enter its code to enable MFA.'));
    }
    public function enable(Request $request) {
        $request->validate(['code'=>['required','regex:/^\d{6}$/']]);
        $setup=$request->session()->get('mfa_setup');
        abort_unless($setup && $setup['expires']>=time() && $setup['user']===$request->user()->id && !$request->user()->mfa_secret,409,__('Setup expired. Start again.'));
        $codes=app(AccountMfa::class)->enable($request->user(),$setup['secret'],$request->code);
        $request->session()->forget('mfa_setup');
        $request->session()->put('mfa_verified',AccountMfa::marker($request->user()));
        return redirect()->route('learn.settings')->with('recovery_codes',$codes)->with('success',__('MFA enabled. Save your recovery codes now; they are shown only once.'));
    }
    public function challenge(Request $request) {
        if(!$request->user()->mfa_secret)return redirect()->route('learn.account');
        return response()->view('learner.mfa')->header('Cache-Control','private, no-store');
    }
    public function verify(Request $request,AccountMfa $mfa) {
        $request->validate(['code'=>'required|string|max:64']);
        if(!$mfa->consume($request->user(),$request->code))return back()->withErrors(['code'=>__('Invalid or already used code. Try the next code or a recovery code.')]);
        $request->session()->regenerate();
        $request->session()->put('mfa_verified',AccountMfa::marker($request->user()));
        return redirect()->route($request->user()->role==='Admin'?'adminDashboard':'learn.account');
    }
    public function disable(Request $request,AccountMfa $mfa) {
        $request->validate(['current_password'=>['required','current_password:web'],'code'=>'required|string|max:64']);
        if(!$mfa->disable($request->user(),$request->code))return back()->withErrors(['code'=>__('Enter a fresh authenticator code or unused recovery code.')]);
        $request->session()->forget('mfa_verified');
        return back()->with('success',__('MFA disabled.'));
    }
}
