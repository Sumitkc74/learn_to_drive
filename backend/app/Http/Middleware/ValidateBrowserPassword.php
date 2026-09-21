<?php
namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthenticationException;

class ValidateBrowserPassword
{
    public function handle($request, \Closure $next)
    {
        $guard=Auth::guard('web');
        $user=$guard->user();
        if(!$user){
            $response=$next($request);
            if($signedIn=$guard->user())$request->session()->put('browser_password.'.$signedIn->getAuthIdentifier(),$signedIn->getAuthPassword());
            return $response;
        }
        $key='browser_password.'.$user->getAuthIdentifier();
        $hash=$user->getAuthPassword();
        $stored=$request->session()->get($key);
        $recalled=$guard->viaRemember() ? explode('|',(string)$request->cookie($guard->getRecallerName())) : null;
        if(($stored===null || !is_string($stored) || !hash_equals($hash,$stored)) || ($recalled!==null && !hash_equals($hash,$recalled[2] ?? ''))){
            $guard->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw new AuthenticationException('Sign in again after your password changed.',['web']);
        }
        $request->session()->put($key,$hash);
        $response=$next($request);
        if($guard->user()?->getAuthIdentifier()===$user->getAuthIdentifier()){
            $request->session()->put($key,$guard->user()->getAuthPassword());
        }
        return $response;
    }
}
