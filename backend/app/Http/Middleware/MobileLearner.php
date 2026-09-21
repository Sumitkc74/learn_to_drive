<?php
namespace App\Http\Middleware;

class MobileLearner
{
    public function handle($request, \Closure $next)
    {
        if($request->user())abort_unless($request->user()->is_active,403,'This account is suspended.');
        $language=$request->header('X-Learner-Language','en');
        app()->setLocale(in_array($language,['en','ne'],true)?$language:'en');
        $response=$next($request);
        $response->headers->set('Cache-Control','private, no-store');
        return $response;
    }
}
