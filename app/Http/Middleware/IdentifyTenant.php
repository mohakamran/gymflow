<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the authenticated user's gym, activates tenant scoping for the request and
 * signs out users whose account or gym has been disabled.
 */
class IdentifyTenant
{
    public function __construct(protected TenantContext $tenants) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenants->set(null);

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            View::share('currentTenant', null);

            return $next($request);
        }

        if (! $user->is_active) {
            return $this->signOut($request, 'Your account has been deactivated. Please contact your gym administrator.');
        }

        if ($user->tenant_id !== null) {
            $tenant = Tenant::find($user->tenant_id);

            if ($tenant === null || ! $tenant->status->canAccessApp()) {
                return $this->signOut($request, 'This gym account is not active. Please contact support.');
            }

            $this->tenants->set($tenant);
        }

        View::share('currentTenant', $this->tenants->get());

        return $next($request);
    }

    protected function signOut(Request $request, string $message): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
