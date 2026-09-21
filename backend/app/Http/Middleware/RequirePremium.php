<?php
namespace App\Http\Middleware;
class RequirePremium {
    public function handle($request,\Closure $next) {
        if(!$request->user()?->hasPremium())return redirect()->route('learn.premium')->with('error','Upgrade to Premium to use this feature.');
        return $next($request);
    }
}
