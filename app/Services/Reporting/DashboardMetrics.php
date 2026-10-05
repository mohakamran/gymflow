<?php

namespace App\Services\Reporting;

use App\Enums\EquipmentStatus;
use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Enums\Role;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Equipment;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * KPIs and chart series for the gym dashboard. All amounts are net of refunds.
 */
class DashboardMetrics
{
    /**
     * @return array<string, mixed>
     */
    public function kpis(): array
    {
        $today = tenant_today();
        $monthStart = $today->startOfMonth();
        $lastMonth = new Period($monthStart->subMonthNoOverflow(), $monthStart->subMonthNoOverflow()->addDays($today->day - 1)->endOfDay());

        $revenueThisMonth = $this->revenue(new Period($monthStart, $today->endOfDay()));
        $revenueLastMonth = $this->revenue($lastMonth);
        $todayVisits = Attendance::query()->today();

        return [
            'members' => Member::query()->where('status', MemberStatus::Active->value)->count(),
            'newMembers' => Member::query()->where('joined_on', '>=', $monthStart->toDateString())->count(),
            'activeMemberships' => Membership::query()->currentlyActive()->count(),
            'expiring' => Membership::query()->expiringSoon()->count(),
            'todayVisits' => (clone $todayVisits)->count(),
            'inGym' => (clone $todayVisits)->open()->count(),
            'revenue' => $revenueThisMonth,
            'revenueChange' => $revenueLastMonth > 0 ? round(($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth * 100) : null,
            'outstanding' => round(Invoice::query()->open()->get(['total', 'amount_paid'])->sum(fn ($invoice) => $invoice->balance()), 2),
            'overdueInvoices' => Invoice::query()->overdue()->count(),
            'trainers' => User::query()->where('is_active', true)->role(Role::Trainer->value)->count(),
            'equipment' => Equipment::query()->toBase()->selectRaw('status, sum(quantity) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'maintenanceDue' => Equipment::query()->maintenanceDue()->count(),
        ];
    }

    public function revenue(Period $period): float
    {
        return round(Payment::query()->successful()->whereBetween('paid_at', $period->utc())->get(['amount', 'refunded_amount'])->sum(fn (Payment $payment) => $payment->netAmount()), 2);
    }

    /**
     * Revenue vs expenses for the last N months.
     *
     * @return array{labels: list<string>, revenue: list<float>, expenses: list<float>, profit: list<float>}
     */
    public function monthlyPerformance(int $months = 6): array
    {
        $period = Period::lastMonths($months);
        $buckets = $period->buckets();

        $revenue = Payment::query()->successful()->whereBetween('paid_at', $period->utc())->get(['paid_at', 'amount', 'refunded_amount'])
            ->groupBy(fn (Payment $payment) => $period->bucket($payment->paid_at))
            ->map(fn ($group) => round($group->sum(fn (Payment $payment) => $payment->netAmount()), 2));

        $expenses = Expense::query()->whereBetween('spent_on', $period->dates())->get(['spent_on', 'amount'])
            ->groupBy(fn (Expense $expense) => $period->bucket($expense->spent_on->toDateString()))
            ->map(fn ($group) => round($group->sum('amount'), 2));

        $revenueSeries = $buckets->keys()->map(fn ($key) => (float) ($revenue[$key] ?? 0))->all();
        $expenseSeries = $buckets->keys()->map(fn ($key) => (float) ($expenses[$key] ?? 0))->all();

        return [
            'labels' => $buckets->values()->all(),
            'revenue' => $revenueSeries,
            'expenses' => $expenseSeries,
            'profit' => array_map(fn ($r, $e) => round($r - $e, 2), $revenueSeries, $expenseSeries),
        ];
    }

    /**
     * Daily visits for the last N days.
     *
     * @return array{labels: list<string>, visits: list<int>}
     */
    public function attendanceTrend(int $days = 30): array
    {
        $period = new Period(tenant_today()->subDays($days - 1), tenant_today()->endOfDay());
        $counts = Attendance::query()->whereBetween('checked_in_at', $period->utc())->get(['checked_in_at'])
            ->groupBy(fn (Attendance $visit) => $period->bucket($visit->checked_in_at))
            ->map->count();

        return [
            'labels' => $period->buckets()->values()->all(),
            'visits' => $period->buckets()->keys()->map(fn ($key) => (int) ($counts[$key] ?? 0))->all(),
        ];
    }

    /**
     * New members per month and total active members at each month end.
     *
     * @return array{labels: list<string>, joined: list<int>}
     */
    public function memberGrowth(int $months = 12): array
    {
        $period = Period::lastMonths($months);
        $joined = Member::query()->whereBetween('joined_on', $period->dates())->get(['joined_on'])
            ->groupBy(fn (Member $member) => $member->joined_on->format('Y-m'))
            ->map->count();

        return [
            'labels' => $period->buckets()->values()->all(),
            'joined' => $period->buckets()->keys()->map(fn ($key) => (int) ($joined[$key] ?? 0))->all(),
        ];
    }

    /**
     * Active memberships by plan.
     *
     * @return array{labels: list<string>, counts: list<int>}
     */
    public function membershipsByPlan(): array
    {
        $rows = Membership::query()->currentlyActive()->with('plan:id,name')->get(['id', 'membership_plan_id'])
            ->groupBy(fn (Membership $membership) => $membership->plan?->name ?? 'Unknown')
            ->map->count()
            ->sortDesc();

        return ['labels' => $rows->keys()->all(), 'counts' => $rows->values()->all()];
    }

    /**
     * Expenses this month by category.
     *
     * @return array{labels: list<string>, amounts: list<float>}
     */
    public function expensesByCategory(): array
    {
        $rows = Expense::query()->where('spent_on', '>=', tenant_today()->startOfMonth()->toDateString())->get(['category', 'amount'])
            ->groupBy(fn (Expense $expense) => $expense->category->label())
            ->map(fn ($group) => round($group->sum('amount'), 2))
            ->sortDesc();

        return ['labels' => $rows->keys()->all(), 'amounts' => $rows->values()->all()];
    }

    /**
     * @return Collection<int, ClassSession>
     */
    public function upcomingClasses(?User $trainer = null, int $limit = 6): Collection
    {
        return ClassSession::query()->upcoming()
            ->when($trainer, fn ($query) => $query->where('trainer_id', $trainer->id))
            ->where('starts_at', '<=', CarbonImmutable::now()->addDays(3))
            ->with(['gymClass', 'trainer'])
            ->withCount('activeBookings')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Membership>
     */
    public function expiringMemberships(int $limit = 6): Collection
    {
        return Membership::query()->expiringSoon()->with(['member', 'plan'])->orderBy('ends_on')->limit($limit)->get();
    }

    public function equipmentAttention(): int
    {
        return Equipment::query()->whereIn('status', [EquipmentStatus::UnderMaintenance->value, EquipmentStatus::Damaged->value])->count();
    }

    public function suspendedMemberships(): int
    {
        return Membership::query()->where('status', MembershipStatus::Suspended->value)->count();
    }
}
