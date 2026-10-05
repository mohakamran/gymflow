<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionPlan;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(TenantStatus::class)],
            'plan' => ['nullable', Rule::enum(SubscriptionPlan::class)],
        ]);

        $tenants = Tenant::query()
            ->withCount('users')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(fn ($inner) => $inner
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('email', "%{$search}%")
                ->orWhereLike('slug', "%{$search}%")))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['plan'] ?? null, fn ($query, string $plan) => $query->where('subscription_plan', $plan))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.tenants.index', ['tenants' => $tenants, 'filters' => $filters]);
    }

    public function show(Tenant $tenant): View
    {
        return view('admin.tenants.show', [
            'tenant' => $tenant->loadCount('users'),
            'owners' => $tenant->users()->role('owner')->get(),
            'activity' => AuditLog::withoutTenancy()->where('tenant_id', $tenant->id)->with('user')->latest('created_at')->limit(15)->get(),
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(TenantStatus::class)],
            'subscription_plan' => ['required', Rule::enum(SubscriptionPlan::class)],
            'trial_ends_at' => ['nullable', 'date'],
        ]);

        $tenant->forceFill($data)->save();

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('toast', ['type' => 'success', 'message' => "{$tenant->name} updated."]);
    }
}
