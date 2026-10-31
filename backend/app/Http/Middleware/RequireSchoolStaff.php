<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSchoolStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_active) {
            abort(403, 'School staff access is required.');
        }

        if (!$user->schoolMembershipsForStaff()->whereHas('school', fn ($query) => $query->where('status', 'Approved'))->exists()) {
            abort(403, 'School staff access is required.');
        }

        return $next($request);
    }
}
