<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limits gym workspace routes to users who belong to a gym.
 */
class EnsureTenantUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->tenant_id === null) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
