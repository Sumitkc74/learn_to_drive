<?php
namespace App\Http\Middleware;
class LearnerSession {
    public function handle($request,\Closure $next){
        if(!$request->user()) return redirect()->guest(route('learn.login'));
        if(!$request->user()->is_active){
            \Illuminate\Support\Facades\Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();
            return redirect()->route('learn.login')->withErrors(['email'=>'Your account is suspended. Contact support.']);
        }
        $response=$next($request);$response->headers->set('Cache-Control','private, no-store');return $response;
    }
}
