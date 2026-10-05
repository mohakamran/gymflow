<?php

namespace App\Services\Reporting;

use App\Enums\BookingStatus;
use App\Enums\ClassSessionStatus;
use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Equipment;
use App\Models\EquipmentMaintenance;
use App\Models\Expense;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\User;
use App\Models\WorkoutPlan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Builds every report as a uniform structure that the screen, CSV, PDF and print views share:
 *
 * [title, description, columns => [key => label], formats => [key => money|number|percent|date|text],
 *  rows => list<array>, totals => array|null, summary => list<[label, value]>, chart => array|null]
 *
 * Aggregation happens in PHP so the same code works on SQLite and MySQL and respects each
 * gym's timezone.
 */
class ReportService
{
    /**
     * @var array<string, array{label: string, description: string, filters: list<string>}>
     */
    public const TYPES = [
        'revenue' => ['label' => 'Revenue', 'description' => 'Payments received, net of refunds.', 'filters' => ['method']],
        'expenses' => ['label' => 'Expenses', 'description' => 'Spending by category.', 'filters' => ['category']],
        'profit' => ['label' => 'Profit & loss', 'description' => 'Revenue minus expenses per period.', 'filters' => []],
        'payments' => ['label' => 'Payments', 'description' => 'Every payment in the period.', 'filters' => ['method', 'payment_status']],
        'memberships' => ['label' => 'Membership sales', 'description' => 'Memberships sold per plan.', 'filters' => ['plan']],
        'expired' => ['label' => 'Expired memberships', 'description' => 'Memberships that ended without a renewal — your win-back list.', 'filters' => ['plan']],
        'members' => ['label' => 'Member growth', 'description' => 'New members joining over time.', 'filters' => []],
        'attendance' => ['label' => 'Attendance', 'description' => 'Visits and unique members per period.', 'filters' => []],
        'trainers' => ['label' => 'Trainers', 'description' => 'Assigned members, classes taught and attendance.', 'filters' => ['trainer']],
        'classes' => ['label' => 'Classes', 'description' => 'Sessions, bookings and fill rate per class.', 'filters' => ['trainer']],
        'equipment' => ['label' => 'Equipment', 'description' => 'Inventory value, status and maintenance cost.', 'filters' => []],
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(string $type, array $filters): array
    {
        if (! array_key_exists($type, self::TYPES)) {
            throw new InvalidArgumentException("Unknown report [$type].");
        }

        $period = Period::make($filters['from'] ?? null, $filters['to'] ?? null);

        $report = $this->{$type}($period, $filters);

        return $report + [
            'type' => $type,
            'title' => self::TYPES[$type]['label'],
            'description' => self::TYPES[$type]['description'],
            'period' => $period,
            'totals' => null,
            'summary' => [],
            'chart' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function revenue(Period $period, array $filters): array
    {
        $payments = $this->paymentsQuery($period, $filters)->successful()->get();
        $grouped = $payments->groupBy(fn (Payment $payment) => $period->bucket($payment->paid_at));

        $rows = $period->buckets()->map(function (string $label, string $key) use ($grouped): array {
            $group = $grouped[$key] ?? collect();

            return [
                'period' => $label,
                'count' => $group->count(),
                'gross' => round($group->sum('amount'), 2),
                'refunds' => round($group->sum('refunded_amount'), 2),
                'net' => round($group->sum(fn (Payment $payment) => $payment->netAmount()), 2),
            ];
        })->values();

        $net = $rows->sum('net');

        return [
            'columns' => ['period' => $period->isMonthly() ? 'Month' : 'Day', 'count' => 'Payments', 'gross' => 'Gross', 'refunds' => 'Refunds', 'net' => 'Net revenue'],
            'formats' => ['count' => 'number', 'gross' => 'money', 'refunds' => 'money', 'net' => 'money'],
            'rows' => $rows->all(),
            'totals' => ['period' => 'Total', 'count' => $rows->sum('count'), 'gross' => $rows->sum('gross'), 'refunds' => $rows->sum('refunds'), 'net' => $net],
            'summary' => [
                ['Net revenue', money($net)],
                ['Payments', number_format($rows->sum('count'))],
                ['Average payment', money($rows->sum('count') ? $net / $rows->sum('count') : 0)],
                ['Top method', $payments->groupBy(fn (Payment $payment) => $payment->method->label())->map->count()->sortDesc()->keys()->first() ?? '—'],
            ],
            'chart' => ['type' => 'bar', 'labels' => $rows->pluck('period')->all(), 'series' => [['label' => 'Net revenue', 'data' => $rows->pluck('net')->all()]], 'format' => 'money'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function expenses(Period $period, array $filters): array
    {
        $expenses = Expense::query()->whereBetween('spent_on', $period->dates())
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->orderBy('spent_on')->get();

        $byCategory = $expenses->groupBy(fn (Expense $expense) => $expense->category->label())->map(fn ($group) => round($group->sum('amount'), 2))->sortDesc();
        $total = round($expenses->sum('amount'), 2);

        return [
            'columns' => ['date' => 'Date', 'category' => 'Category', 'title' => 'Description', 'vendor' => 'Vendor', 'amount' => 'Amount'],
            'formats' => ['amount' => 'money'],
            'rows' => $expenses->map(fn (Expense $expense): array => [
                'date' => format_date($expense->spent_on),
                'category' => $expense->category->label(),
                'title' => $expense->title,
                'vendor' => $expense->vendor ?? '—',
                'amount' => (float) $expense->amount,
            ])->all(),
            'totals' => ['date' => 'Total', 'category' => '', 'title' => '', 'vendor' => '', 'amount' => $total],
            'summary' => [
                ['Total expenses', money($total)],
                ['Entries', number_format($expenses->count())],
                ['Largest category', $byCategory->keys()->first() ?? '—'],
            ],
            'chart' => ['type' => 'hbar', 'labels' => $byCategory->keys()->all(), 'series' => [['label' => 'Expenses', 'data' => $byCategory->values()->all()]], 'format' => 'money'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function profit(Period $period, array $filters): array
    {
        $revenue = Payment::query()->successful()->whereBetween('paid_at', $period->utc())->get()
            ->groupBy(fn (Payment $payment) => $period->bucket($payment->paid_at))
            ->map(fn ($group) => round($group->sum(fn (Payment $payment) => $payment->netAmount()), 2));
        $expenses = Expense::query()->whereBetween('spent_on', $period->dates())->get()
            ->groupBy(fn (Expense $expense) => $period->bucket($expense->spent_on->toDateString()))
            ->map(fn ($group) => round($group->sum('amount'), 2));

        $rows = $period->buckets()->map(function (string $label, string $key) use ($revenue, $expenses): array {
            $in = (float) ($revenue[$key] ?? 0);
            $out = (float) ($expenses[$key] ?? 0);

            return ['period' => $label, 'revenue' => $in, 'expenses' => $out, 'profit' => round($in - $out, 2), 'margin' => $in > 0 ? round(($in - $out) / $in * 100, 1) : null];
        })->values();

        $in = $rows->sum('revenue');
        $out = $rows->sum('expenses');

        return [
            'columns' => ['period' => $period->isMonthly() ? 'Month' : 'Day', 'revenue' => 'Revenue', 'expenses' => 'Expenses', 'profit' => 'Profit', 'margin' => 'Margin'],
            'formats' => ['revenue' => 'money', 'expenses' => 'money', 'profit' => 'money', 'margin' => 'percent'],
            'rows' => $rows->all(),
            'totals' => ['period' => 'Total', 'revenue' => $in, 'expenses' => $out, 'profit' => round($in - $out, 2), 'margin' => $in > 0 ? round(($in - $out) / $in * 100, 1) : null],
            'summary' => [
                ['Revenue', money($in)],
                ['Expenses', money($out)],
                ['Profit', money($in - $out)],
                ['Margin', $in > 0 ? round(($in - $out) / $in * 100, 1).'%' : '—'],
            ],
            'chart' => ['type' => 'bar', 'labels' => $rows->pluck('period')->all(), 'series' => [
                ['label' => 'Revenue', 'data' => $rows->pluck('revenue')->all()],
                ['label' => 'Expenses', 'data' => $rows->pluck('expenses')->all()],
            ], 'format' => 'money'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function payments(Period $period, array $filters): array
    {
        $payments = $this->paymentsQuery($period, $filters)
            ->when($filters['payment_status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->with(['member', 'invoice'])->orderBy('paid_at')->get();

        return [
            'columns' => ['date' => 'Date', 'reference' => 'Reference', 'member' => 'Member', 'invoice' => 'Invoice', 'method' => 'Method', 'status' => 'Status', 'amount' => 'Amount', 'refunded' => 'Refunded'],
            'formats' => ['amount' => 'money', 'refunded' => 'money'],
            'rows' => $payments->map(fn (Payment $payment): array => [
                'date' => format_date($payment->paid_at, true),
                'reference' => $payment->reference,
                'member' => $payment->member?->full_name ?? '—',
                'invoice' => $payment->invoice?->number ?? '—',
                'method' => $payment->method->label(),
                'status' => $payment->status->label(),
                'amount' => (float) $payment->amount,
                'refunded' => (float) $payment->refunded_amount,
            ])->all(),
            'totals' => ['date' => 'Total', 'reference' => '', 'member' => '', 'invoice' => '', 'method' => '', 'status' => '', 'amount' => round($payments->sum('amount'), 2), 'refunded' => round($payments->sum('refunded_amount'), 2)],
            'summary' => [
                ['Collected', money($payments->whereNotIn('status', [PaymentStatus::Failed, PaymentStatus::Pending])->sum(fn (Payment $payment) => $payment->netAmount()))],
                ['Payments', number_format($payments->count())],
                ['Refunded', money($payments->sum('refunded_amount'))],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function memberships(Period $period, array $filters): array
    {
        $sold = Membership::query()->whereBetween('created_at', $period->utc())
            ->when($filters['plan'] ?? null, fn ($query, $plan) => $query->where('membership_plan_id', $plan))
            ->with(['plan', 'invoice'])->get();

        $activeByPlan = Membership::query()->currentlyActive()->get(['membership_plan_id'])->countBy('membership_plan_id');

        $rows = $sold->groupBy('membership_plan_id')->map(fn (Collection $group): array => [
            'plan' => $group->first()->plan?->name ?? 'Unknown',
            'sold' => $group->count(),
            'new' => $group->whereNull('renewed_from_id')->count(),
            'renewals' => $group->whereNotNull('renewed_from_id')->count(),
            'value' => round($group->sum(fn (Membership $membership) => (float) $membership->price - (float) $membership->discount_amount), 2),
            'active' => (int) ($activeByPlan[$group->first()->membership_plan_id] ?? 0),
        ])->sortByDesc('sold')->values();

        return [
            'columns' => ['plan' => 'Plan', 'sold' => 'Sold', 'new' => 'New', 'renewals' => 'Renewals', 'value' => 'Sales value', 'active' => 'Active now'],
            'formats' => ['sold' => 'number', 'new' => 'number', 'renewals' => 'number', 'value' => 'money', 'active' => 'number'],
            'rows' => $rows->all(),
            'totals' => ['plan' => 'Total', 'sold' => $rows->sum('sold'), 'new' => $rows->sum('new'), 'renewals' => $rows->sum('renewals'), 'value' => $rows->sum('value'), 'active' => $rows->sum('active')],
            'summary' => [
                ['Memberships sold', number_format($rows->sum('sold'))],
                ['Renewal share', $rows->sum('sold') ? round($rows->sum('renewals') / $rows->sum('sold') * 100).'%' : '—'],
                ['Sales value', money($rows->sum('value'))],
                ['Active memberships', number_format(Membership::query()->currentlyActive()->count())],
            ],
            'chart' => ['type' => 'hbar', 'labels' => $rows->pluck('plan')->all(), 'series' => [['label' => 'Sold', 'data' => $rows->pluck('sold')->all()]], 'format' => 'number'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function expired(Period $period, array $filters): array
    {
        $ended = Membership::query()
            ->whereIn('status', [MembershipStatus::Expired->value, MembershipStatus::Active->value])
            ->whereBetween('ends_on', [$period->from->toDateString(), min($period->to, tenant_today()->subDay())->toDateString()])
            ->when($filters['plan'] ?? null, fn ($query, $plan) => $query->where('membership_plan_id', $plan))
            ->with(['member', 'plan'])->orderByDesc('ends_on')->get()
            ->filter(fn (Membership $membership) => ! Membership::query()->where('member_id', $membership->member_id)->where('starts_on', '>', $membership->ends_on)->exists());

        return [
            'columns' => ['member' => 'Member', 'code' => 'Code', 'plan' => 'Plan', 'ended' => 'Ended', 'days' => 'Days lapsed', 'email' => 'Email', 'phone' => 'Phone'],
            'formats' => ['days' => 'number'],
            'rows' => $ended->map(fn (Membership $membership): array => [
                'member' => $membership->member?->full_name ?? '—',
                'code' => $membership->member?->member_code,
                'plan' => $membership->plan?->name,
                'ended' => format_date($membership->ends_on),
                'days' => (int) $membership->ends_on->diffInDays(tenant_today()),
                'email' => $membership->member?->email ?? '—',
                'phone' => $membership->member?->phone ?? '—',
            ])->values()->all(),
            'summary' => [['Lapsed members', number_format($ended->count())]],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function members(Period $period, array $filters): array
    {
        $joined = Member::query()->whereBetween('joined_on', $period->dates())->get(['joined_on'])
            ->groupBy(fn (Member $member) => $period->bucket($member->joined_on->toDateString()))->map->count();
        $runningTotal = Member::query()->where('joined_on', '<', $period->from->toDateString())->count();

        $rows = $period->buckets()->map(function (string $label, string $key) use ($joined, &$runningTotal): array {
            $new = (int) ($joined[$key] ?? 0);
            $runningTotal += $new;

            return ['period' => $label, 'new' => $new, 'total' => $runningTotal];
        })->values();

        return [
            'columns' => ['period' => $period->isMonthly() ? 'Month' : 'Day', 'new' => 'New members', 'total' => 'Total members'],
            'formats' => ['new' => 'number', 'total' => 'number'],
            'rows' => $rows->all(),
            'totals' => ['period' => 'Total', 'new' => $rows->sum('new'), 'total' => $rows->last()['total'] ?? 0],
            'summary' => [['New members', number_format($rows->sum('new'))], ['Total members', number_format($rows->last()['total'] ?? 0)]],
            'chart' => ['type' => 'bar', 'labels' => $rows->pluck('period')->all(), 'series' => [['label' => 'New members', 'data' => $rows->pluck('new')->all()]], 'format' => 'number'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function attendance(Period $period, array $filters): array
    {
        $visits = Attendance::query()->whereBetween('checked_in_at', $period->utc())->get(['member_id', 'checked_in_at', 'checked_out_at']);
        $grouped = $visits->groupBy(fn (Attendance $visit) => $period->bucket($visit->checked_in_at));

        $rows = $period->buckets()->map(function (string $label, string $key) use ($grouped): array {
            $group = $grouped[$key] ?? collect();
            $durations = $group->map->durationMinutes()->filter();

            return ['period' => $label, 'visits' => $group->count(), 'unique' => $group->unique('member_id')->count(), 'duration' => $durations->isNotEmpty() ? (int) round($durations->avg()) : null];
        })->values();

        $busiestHour = $visits->groupBy(fn (Attendance $visit) => $visit->checked_in_at->copy()->setTimezone(tenant_timezone())->format('G'))->map->count()->sortDesc()->keys()->first();

        return [
            'columns' => ['period' => $period->isMonthly() ? 'Month' : 'Day', 'visits' => 'Visits', 'unique' => 'Unique members', 'duration' => 'Avg. minutes'],
            'formats' => ['visits' => 'number', 'unique' => 'number', 'duration' => 'number'],
            'rows' => $rows->all(),
            'totals' => ['period' => 'Total', 'visits' => $visits->count(), 'unique' => $visits->unique('member_id')->count(), 'duration' => null],
            'summary' => [
                ['Visits', number_format($visits->count())],
                ['Unique members', number_format($visits->unique('member_id')->count())],
                ['Busiest hour', $busiestHour !== null ? CarbonImmutable::createFromTime((int) $busiestHour)->format('g A') : '—'],
            ],
            'chart' => ['type' => 'line', 'labels' => $rows->pluck('period')->all(), 'series' => [['label' => 'Visits', 'data' => $rows->pluck('visits')->all()]], 'format' => 'number'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function trainers(Period $period, array $filters): array
    {
        $trainers = User::query()->role(Role::Trainer->value)->withCount('assignedMembers')
            ->when($filters['trainer'] ?? null, fn ($query, $trainer) => $query->whereKey($trainer))->orderBy('name')->get();
        $sessions = ClassSession::query()->whereBetween('starts_at', $period->utc())->where('status', '!=', ClassSessionStatus::Cancelled->value)
            ->withCount(['bookings as attended_count' => fn ($query) => $query->where('status', BookingStatus::Attended->value)])->get(['id', 'trainer_id']);
        $plans = WorkoutPlan::query()->whereBetween('created_at', $period->utc())->get(['trainer_id'])->countBy('trainer_id');

        $rows = $trainers->map(fn (User $trainer): array => [
            'trainer' => $trainer->name,
            'members' => $trainer->assigned_members_count,
            'sessions' => $sessions->where('trainer_id', $trainer->id)->count(),
            'attended' => $sessions->where('trainer_id', $trainer->id)->sum('attended_count'),
            'plans' => (int) ($plans[$trainer->id] ?? 0),
            'status' => $trainer->is_active ? 'Active' : 'Inactive',
        ]);

        return [
            'columns' => ['trainer' => 'Trainer', 'members' => 'Assigned members', 'sessions' => 'Classes taught', 'attended' => 'Class attendees', 'plans' => 'Workout plans', 'status' => 'Status'],
            'formats' => ['members' => 'number', 'sessions' => 'number', 'attended' => 'number', 'plans' => 'number'],
            'rows' => $rows->all(),
            'summary' => [['Trainers', number_format($rows->count())], ['Classes taught', number_format($rows->sum('sessions'))]],
            'chart' => $rows->isEmpty() ? null : ['type' => 'hbar', 'labels' => $rows->pluck('trainer')->all(), 'series' => [['label' => 'Classes taught', 'data' => $rows->pluck('sessions')->all()]], 'format' => 'number'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function classes(Period $period, array $filters): array
    {
        $sessions = ClassSession::query()->whereBetween('starts_at', $period->utc())
            ->when($filters['trainer'] ?? null, fn ($query, $trainer) => $query->where('trainer_id', $trainer))
            ->with('gymClass')
            ->withCount([
                'bookings as booked_count' => fn ($query) => $query->whereIn('status', BookingStatus::occupying()),
                'bookings as attended_count' => fn ($query) => $query->where('status', BookingStatus::Attended->value),
                'bookings as no_show_count' => fn ($query) => $query->where('status', BookingStatus::NoShow->value),
            ])->get();

        $rows = $sessions->groupBy('gym_class_id')->map(function (Collection $group): array {
            $active = $group->where('status', '!=', ClassSessionStatus::Cancelled);
            $capacity = $active->sum('capacity');

            return [
                'class' => $group->first()->gymClass?->name ?? 'Unknown',
                'sessions' => $active->count(),
                'cancelled' => $group->count() - $active->count(),
                'capacity' => $capacity,
                'booked' => $active->sum('booked_count'),
                'attended' => $active->sum('attended_count'),
                'no_show' => $active->sum('no_show_count'),
                'fill' => $capacity > 0 ? round($active->sum('booked_count') / $capacity * 100, 1) : null,
            ];
        })->sortByDesc('booked')->values();

        return [
            'columns' => ['class' => 'Class', 'sessions' => 'Sessions', 'cancelled' => 'Cancelled', 'capacity' => 'Capacity', 'booked' => 'Booked', 'attended' => 'Attended', 'no_show' => 'No-shows', 'fill' => 'Fill rate'],
            'formats' => ['sessions' => 'number', 'cancelled' => 'number', 'capacity' => 'number', 'booked' => 'number', 'attended' => 'number', 'no_show' => 'number', 'fill' => 'percent'],
            'rows' => $rows->all(),
            'summary' => [
                ['Sessions', number_format($rows->sum('sessions'))],
                ['Bookings', number_format($rows->sum('booked'))],
                ['Average fill rate', $rows->sum('capacity') ? round($rows->sum('booked') / $rows->sum('capacity') * 100).'%' : '—'],
            ],
            'chart' => $rows->isEmpty() ? null : ['type' => 'hbar', 'labels' => $rows->pluck('class')->all(), 'series' => [['label' => 'Fill rate %', 'data' => $rows->pluck('fill')->all()]], 'format' => 'number'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function equipment(Period $period, array $filters): array
    {
        $items = Equipment::query()->orderBy('category')->orderBy('name')->get();
        $maintenanceCost = EquipmentMaintenance::query()->whereBetween('performed_on', $period->dates())->get(['equipment_id', 'cost'])->groupBy('equipment_id')->map(fn ($group) => round($group->sum('cost'), 2));

        return [
            'columns' => ['name' => 'Equipment', 'category' => 'Category', 'quantity' => 'Qty', 'status' => 'Status', 'condition' => 'Condition', 'value' => 'Asset value', 'maintenance' => 'Maintenance cost', 'next' => 'Next maintenance'],
            'formats' => ['quantity' => 'number', 'value' => 'money', 'maintenance' => 'money'],
            'rows' => $items->map(fn (Equipment $item): array => [
                'name' => $item->name,
                'category' => $item->category->label(),
                'quantity' => $item->quantity,
                'status' => $item->status->label(),
                'condition' => $item->condition->label(),
                'value' => round((float) $item->cost * $item->quantity, 2),
                'maintenance' => (float) ($maintenanceCost[$item->id] ?? 0),
                'next' => format_date($item->next_maintenance_on),
            ])->all(),
            'totals' => ['name' => 'Total', 'category' => '', 'quantity' => $items->sum('quantity'), 'status' => '', 'condition' => '', 'value' => round($items->sum(fn ($item) => (float) $item->cost * $item->quantity), 2), 'maintenance' => $maintenanceCost->sum(), 'next' => ''],
            'summary' => [
                ['Items', number_format($items->sum('quantity'))],
                ['Asset value', money($items->sum(fn ($item) => (float) $item->cost * $item->quantity))],
                ['Maintenance spend', money($maintenanceCost->sum())],
                ['Due for maintenance', number_format(Equipment::query()->maintenanceDue()->count())],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Payment>
     */
    protected function paymentsQuery(Period $period, array $filters): Builder
    {
        return Payment::query()->whereBetween('paid_at', $period->utc())
            ->when($filters['method'] ?? null, fn ($query, $method) => $query->where('method', $method));
    }

    /**
     * Format a raw cell for display/export.
     */
    public static function cell(mixed $value, ?string $format): string
    {
        if ($value === null || $value === '') {
            return $value === '' ? '' : '—';
        }

        return match ($format) {
            'money' => money($value),
            'number' => number_format((float) $value),
            'percent' => $value.'%',
            default => (string) $value,
        };
    }
}
