<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash,Password,Cache,Crypt};
use Illuminate\Validation\Rule;
use App\Services\AccountMfa;
use PragmaRX\Google2FA\Google2FA;

class LearnerAccountController extends Controller
{
    private function password(Request $request): void
    {
        $request->validate(['current_password'=>'required|string|max:1000']);
        if(!Hash::check($request->current_password,$request->user()->password))throw \Illuminate\Validation\ValidationException::withMessages(['current_password'=>'The current password is incorrect.']);
    }
    public function detail(Request $request,string $field)
    {
        abort_unless(in_array($field,['name','email','phoneNumber']),404);
        $rules=match($field){'name'=>['required','string','max:100'],'email'=>['required','email','max:255',Rule::unique('users')->ignore($request->user()->id)],'phoneNumber'=>['required','digits:10']};
        $data=$request->validate([$field=>$rules]);
        if($field==='email')$this->password($request);
        $user=$request->user();$user->fill($data);
        if($user->isDirty('email'))$user->forceFill(['email_verified_at'=>null]);
        if($user->isDirty('phoneNumber'))$user->forceFill(['phone_verified_at'=>null,'phone_verification_code'=>null,'phone_verification_expires_at'=>null]);
        $user->save();
        return response()->json(['message'=>__('Your details have been updated.')]);
    }
    public function forgot(Request $request)
    {
        $data=$request->validate(['email'=>'required|email|max:255']);
        try{Password::broker()->sendResetLink($data+['is_active'=>true],fn($user,$token)=>$user->notify(new \App\Notifications\LearnerResetPassword($token)));}
        catch(\Throwable $e){report($e);}
        return response()->json(['message'=>__('If an active account matches that email, a password reset link has been sent. Check your inbox and spam folder.')]);
    }
    public function verification(Request $request)
    {
        if(!$request->user()->hasVerifiedEmail()){
            try{$request->user()->notify(new \App\Notifications\LearnerVerifyEmail);}
            catch(\Throwable $e){report($e);return response()->json(['message'=>'Verification email is temporarily unavailable.'],503);}
        }
        return response()->json(['message'=>__('Verification email sent. Check your inbox and spam folder. The link expires in 60 minutes.')]);
    }
    public function setup(Request $request)
    {
        $this->password($request);abort_if((bool)$request->user()->mfa_secret,409);
        $secret=(new Google2FA)->generateSecretKey();
        Cache::put('mobile-mfa:'.$request->user()->id,Crypt::encryptString($secret),now()->addMinutes(10));
        return response()->json(['data'=>['secret'=>$secret],'message'=>'Add this key to your authenticator. It expires in 10 minutes.'])->header('Cache-Control','no-store');
    }
    public function enable(Request $request)
    {
        $this->password($request);$request->validate(['code'=>['required','regex:/^\d{6}$/']]);
        return Cache::lock('mobile-mfa-lock:'.$request->user()->id,10)->block(3,function()use($request){
            $user=$request->user()->fresh();$encrypted=Cache::get('mobile-mfa:'.$user->id);
            abort_unless($encrypted && !$user->mfa_secret,409);
            $codes=app(AccountMfa::class)->enable($user,Crypt::decryptString($encrypted),$request->code,$request->user()->currentAccessToken()?->getKey());
            Cache::forget('mobile-mfa:'.$user->id);
            return response()->json(['data'=>['recovery_codes'=>$codes],'message'=>'MFA enabled. Store your recovery codes safely.'])->header('Cache-Control','no-store');
        });
    }
    public function disable(Request $request,AccountMfa $mfa)
    {
        $this->password($request);$request->validate(['code'=>'required|string|max:64']);
        if(!$mfa->disable($request->user(),$request->code))throw \Illuminate\Validation\ValidationException::withMessages(['code'=>'Enter a fresh code or unused recovery code.']);
        return response()->json(['message'=>__('MFA disabled.')]);
    }
}
