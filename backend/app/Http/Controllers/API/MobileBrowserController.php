<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountMfa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,Cache};

class MobileBrowserController extends Controller
{
    private function marker(User $user): string { return hash('sha256', AccountMfa::marker($user).$user->password); }

    public function start(Request $request)
    {
        $id=bin2hex(random_bytes(24));$secret=bin2hex(random_bytes(32));
        Cache::put('mobile-login:'.$id,['verifier'=>hash('sha256',$secret),'user'=>null,'code'=>strtoupper(bin2hex(random_bytes(3)))],now()->addMinutes(5));
        return response()->json(['data'=>['id'=>$id,'verifier'=>$secret,'url'=>route('learn.mobile.connect',$id),'code'=>Cache::get('mobile-login:'.$id)['code']]])->header('Cache-Control','no-store');
    }
    public function connect(Request $request,string $id)
    {
        abort_unless(preg_match('/^[a-f0-9]{48}$/',$id),404);
        $flow=Cache::get('mobile-login:'.$id);abort_unless($flow,410);
        $request->session()->put('mobile_login_id',$id);
        if(!$request->user())return redirect()->route('learn.login');
        abort_unless($request->user()->is_active,403);
        return response()->view('learner.mobile-connect',compact('id','flow'))->header('Cache-Control','no-store')->header('Referrer-Policy','no-referrer');
    }
    public function approve(Request $request,string $id)
    {
        abort_unless($request->user()?->is_active,403);
        abort_unless(!$request->user()->mfa_secret || $request->session()->get('mfa_verified')===AccountMfa::marker($request->user()),403);
        Cache::lock('mobile-login-lock:'.$id,10)->block(3,function()use($id,$request){
            $flow=Cache::get('mobile-login:'.$id);abort_unless($flow && !$flow['user'],410);
            $flow['user']=$request->user()->id;$flow['marker']=$this->marker($request->user());
            Cache::put('mobile-login:'.$id,$flow,now()->addMinute());
        });
        $request->session()->forget('mobile_login_id');
        return redirect()->route('learn.account')->with('success','Mobile sign-in approved. Return to the app and select Complete sign-in.');
    }
    public function exchange(Request $request)
    {
        $data=$request->validate(['id'=>'required|regex:/^[a-f0-9]{48}$/','verifier'=>'required|string|size:64']);
        return Cache::lock('mobile-login-lock:'.$data['id'],10)->block(3,function()use($data){
            $key='mobile-login:'.$data['id'];$flow=Cache::get($key);
            abort_unless($flow && hash_equals($flow['verifier'],hash('sha256',$data['verifier'])),403);
            if(!$flow['user'])return response()->json(['message'=>'Approve this sign-in in your browser first.'],409);
            $user=User::findOrFail($flow['user']);abort_unless($user->is_active && hash_equals($flow['marker'],$this->marker($user)),403);
            Cache::forget($key);
            $expires=now()->addDays(\App\Models\AppSetting::read('access_token_expiry_days'));
            $token=$user->createToken('Flutter mobile',['*'],$expires);
            return response()->json(['user'=>$user->only(['id','name','email','phoneNumber','role']),'token'=>['access_token'=>$token->plainTextToken,'token_type'=>'Bearer','expires_at'=>$expires]])->header('Cache-Control','no-store');
        });
    }
    public function handoff(Request $request)
    {
        $data=$request->validate(['destination'=>'required|in:premium,payments,settings,account,practice.attempt','attempt_id'=>'required_if:destination,practice.attempt|nullable|integer','language'=>'nullable|in:en,ne','practice_language'=>'nullable|in:en,ne']);
        if($data['destination']==='practice.attempt')\App\Models\LearnerPracticeAttempt::where('user_id',$request->user()->id)->findOrFail($data['attempt_id']);
        $id=bin2hex(random_bytes(32));
        Cache::put('mobile-handoff:'.$id,['user'=>$request->user()->id,'destination'=>$data['destination'],'attempt_id'=>$data['attempt_id'] ?? null,'language'=>$data['language'] ?? 'en','practice_language'=>$data['practice_language'] ?? null,'marker'=>$this->marker($request->user()),'token_id'=>$request->user()->currentAccessToken()?->getKey()],now()->addMinute());
        return response()->json(['data'=>['url'=>route('learn.mobile.handoff',$id)]])->header('Cache-Control','no-store');
    }
    public function handoffForm(string $id)
    {
        $flow=Cache::get('mobile-handoff:'.$id);abort_unless($flow,410);
        $user=User::findOrFail($flow['user']);abort_unless($user->is_active,403);
        return response()->view('learner.mobile-handoff',compact('id','user'))->header('Cache-Control','no-store')->header('Referrer-Policy','no-referrer');
    }
    public function accept(Request $request,string $id)
    {
        return Cache::lock('mobile-handoff-lock:'.$id,10)->block(3,function()use($id,$request){
            $flow=Cache::pull('mobile-handoff:'.$id);abort_unless($flow,410);
            $token=\Laravel\Sanctum\PersonalAccessToken::find($flow['token_id']);
            abort_unless($token && (!$token->expires_at || $token->expires_at->isFuture()),403);
            $user=User::findOrFail($flow['user']);abort_unless($user->is_active && hash_equals($flow['marker'],$this->marker($user)),403);
            Auth::guard('web')->login($user);$request->session()->regenerate();
            $request->session()->put('mfa_verified',AccountMfa::marker($user));
            $request->session()->put('learner_locale',$flow['language'] ?? 'en');
            if(!empty($flow['practice_language']))$request->session()->put('practice_language',$flow['practice_language']);
            if($flow['destination']==='practice.attempt')\App\Models\LearnerPracticeAttempt::where('user_id',$user->id)->findOrFail($flow['attempt_id']);
            return redirect()->route('learn.'.$flow['destination'],$flow['destination']==='practice.attempt'?['id'=>$flow['attempt_id']]:[]);
        });
    }
}
