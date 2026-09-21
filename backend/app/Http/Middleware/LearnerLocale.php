<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LearnerLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Keep the administrative interface and API independent of learner preferences.
        $locale = $request->is('learn', 'learn/*', '/')
            ? $request->session()->get('learner_locale', $request->cookie('learner_locale', 'en')) : 'en';
        app()->setLocale(in_array($locale, ['en', 'ne'], true) ? $locale : 'en');
        return $next($request);
    }
}
