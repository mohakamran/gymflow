<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PlanChangeRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $statusCounts = Tenant::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.dashboard', [
            'stats' => [
                'gyms' => $statusCounts->sum(),
                'active' => (int) ($statusCounts[TenantStatus::Active->value] ?? 0),
                'trial' => (int) ($statusCounts[TenantStatus::Trial->value] ?? 0),
                'suspended' => (int) ($statusCounts[TenantStatus::Suspended->value] ?? 0),
                'users' => User::withoutTenancy()->whereNotNull('tenant_id')->count(),
                'signupsThisMonth' => Tenant::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'latestGyms' => Tenant::query()->withCount('users')->latest()->limit(8)->get(),
            'pendingRequests' => PlanChangeRequest::withoutTenancy()->where('status', 'pending')->count(),
            'recentActivity' => AuditLog::withoutTenancy()->with('user')->latest('created_at')->limit(10)->get(),
        ]);
    }
}
