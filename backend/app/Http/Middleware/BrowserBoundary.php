<?php
namespace App\Http\Middleware;

class BrowserBoundary
{
    public function handle($request, \Closure $next)
    {
        $host=strtolower((string)parse_url(config('app.url'),PHP_URL_HOST));
        $allowed=[$host];
        if(app()->environment('local','testing'))$allowed=array_merge($allowed,['localhost','127.0.0.1','::1','[::1]']);
        abort_unless($host && in_array(strtolower($request->getHost()),$allowed,true),400,'Untrusted request host.');
        $response=$next($request);
        $response->headers->set('X-Frame-Options','DENY');
        $policy=$response->headers->get('Content-Security-Policy','');
        if(!str_contains(strtolower($policy),'frame-ancestors')){
            $response->headers->set('Content-Security-Policy',rtrim($policy,'; ').($policy?'; ':'')."frame-ancestors 'none'");
        }
        return $response;
    }
}
