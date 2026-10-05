<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\SubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PlanChangeRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Platform-wide (cross-tenant) administration for the super admin.
 */
class PlatformController extends Controller
{
    public function users(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(Role::class)],
        ]);

        $users = User::withoutTenancy()
            ->with(['roles', 'tenant'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->role($role))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.users', ['users' => $users, 'filters' => $filters]);
    }

    public function toggleUser(Request $request, int $user): RedirectResponse
    {
        $account = User::withoutTenancy()->findOrFail($user);
        abort_if($account->is($request->user()), 422, 'You cannot deactivate yourself.');

        $account->forceFill(['is_active' => ! $account->is_active])->save();

        if (! $account->is_active) {
            DB::table('sessions')->where('user_id', $account->id)->delete();
        }

        return $this->done(back(), $account->is_active ? "{$account->name} reactivated." : "{$account->name} deactivated.");
    }

    public function plans(): View
    {
        return view('admin.plans', [
            'plans' => SubscriptionPlan::cases(),
            'distribution' => Tenant::query()->toBase()->selectRaw('subscription_plan, count(*) as total')->groupBy('subscription_plan')->pluck('total', 'subscription_plan'),
            'requests' => PlanChangeRequest::withoutTenancy()->with(['tenant', 'requester'])->orderByRaw("case when status = 'pending' then 0 else 1 end")->latest()->limit(50)->get(),
        ]);
    }

    public function resolveRequest(Request $request, int $planRequest): RedirectResponse
    {
        $change = PlanChangeRequest::withoutTenancy()->with('tenant')->findOrFail($planRequest);
        $decision = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])]])['decision'];
        abort_unless($change->status === 'pending', 422);

        DB::transaction(function () use ($change, $decision): void {
            $change->forceFill(['status' => $decision, 'resolved_at' => now()])->save();

            if ($decision === 'approved') {
                $change->tenant->forceFill(['subscription_plan' => $change->requested_plan])->save();
            }
        });

        return $this->done(back(), "Request {$decision}.");
    }

    public function activity(Request $request): View
    {
        $filters = $request->validate(['event' => ['nullable', 'string', 'max:50'], 'tenant' => ['nullable', 'integer']]);

        return view('admin.activity', [
            'logs' => AuditLog::withoutTenancy()->with(['user', 'tenant'])
                ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', 'like', $event.'%'))
                ->when($filters['tenant'] ?? null, fn ($query, $tenant) => $query->where('tenant_id', $tenant))
                ->latest('created_at')->latest('id')->paginate(30)->withQueryString(),
            'filters' => $filters,
            'tenants' => Tenant::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
