<?php

namespace App\Http\Middleware;

// The school-centre API routes share controllers with the web school-centre
// pages, and those controllers render Blade views for requests that do not
// expect JSON. The API group has no session middleware, so the learner
// layout's $errors (shared by ShareErrorsFromSession on web only) is
// undefined and the view throws a 500. An API route must always answer
// JSON, even when the client forgets the Accept header — so force it here.
// (Unauthenticated requests still redirect to the login page first:
// Laravel's middleware priority runs auth before route-group middleware,
// so the forced header cannot apply there. That is pre-existing behaviour.)
class ForceJsonResponse
{
    public function handle($request, \Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
