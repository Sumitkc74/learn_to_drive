<?php
namespace App\Http\Middleware;
use App\Services\AccountMfa;
class RequireAccountMfa {
    public function handle($request,\Closure $next) {
        $user=$request->user();
        if($user?->mfa_secret && $request->session()->get('mfa_verified')!==AccountMfa::marker($user)
            && !$request->routeIs('learn.mfa.challenge','learn.mfa.verify','learn.logout','logout','learn.language')) {
            return redirect()->route('learn.mfa.challenge');
        }
        return $next($request);
    }
}
