<?php

namespace App\Http\Controllers\Members;

use App\Enums\AttendanceMethod;
use App\Enums\Permission;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Member;
use App\Services\AttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(protected AttendanceService $attendance) {}

    /**
     * Front desk: live list of today's visits plus check-in.
     */
    public function index(): View
    {
        $visits = Attendance::query()->today()->with(['member.currentMembership.plan', 'recorder'])->latest('checked_in_at')->get();

        return view('attendance.index', [
            'visits' => $visits,
            'inGym' => $visits->whereNull('checked_out_at')->count(),
            'hourly' => $visits->groupBy(fn (Attendance $visit) => (int) $visit->checked_in_at->copy()->setTimezone(tenant_timezone())->format('G'))->map->count(),
        ]);
    }

    /**
     * Visit history with date range filters.
     */
    public function history(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'method' => ['nullable', Rule::enum(AttendanceMethod::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $from = $filters['from'] ?? tenant_today()->subDays(6)->toDateString();
        $to = $filters['to'] ?? tenant_today()->toDateString();

        $query = Attendance::query()
            ->betweenDays($from, $to)
            ->when($filters['method'] ?? null, fn ($query, $method) => $query->where('method', $method))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas('member', fn ($inner) => $inner->search($search)));

        $daily = (clone $query)->get(['checked_in_at'])
            ->groupBy(fn (Attendance $visit) => $visit->checked_in_at->copy()->setTimezone(tenant_timezone())->toDateString())
            ->map->count();

        $period = collect(CarbonImmutable::parse($from)->daysUntil(CarbonImmutable::parse($to)))->mapWithKeys(fn ($day) => [$day->toDateString() => $daily[$day->toDateString()] ?? 0]);

        return view('attendance.history', [
            'visits' => $query->with(['member', 'recorder'])->latest('checked_in_at')->paginate(30)->withQueryString(),
            'filters' => $filters + ['from' => $from, 'to' => $to],
            'daily' => $period,
            'uniqueMembers' => (clone $query)->distinct()->count('member_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['nullable', 'integer', 'required_without:code'],
            'code' => ['nullable', 'string', 'max:120', 'required_without:member_id'],
            'method' => ['nullable', Rule::enum(AttendanceMethod::class)],
        ]);

        $member = isset($data['member_id'])
            ? Member::find($data['member_id'])
            : $this->attendance->resolveMember($data['code']);

        if ($member === null) {
            throw new BusinessRuleException('No member matches that code.');
        }

        $method = AttendanceMethod::tryFrom($data['method'] ?? '') ?? (isset($data['code']) ? AttendanceMethod::Qr : AttendanceMethod::Manual);
        $action = $request->boolean('toggle')
            ? $this->attendance->toggle($member, $method)['action']
            : tap('in', fn () => $this->attendance->checkIn($member, $method));

        $message = $action === 'in' ? "{$member->full_name} checked in." : "{$member->full_name} checked out.";

        if ($request->expectsJson()) {
            $member->loadMissing('currentMembership.plan');

            return response()->json([
                'message' => $message,
                'action' => $action,
                'member' => [
                    'name' => $member->full_name,
                    'initials' => $member->initials,
                    'photo' => $member->photo_url,
                    'plan' => $member->currentMembership?->plan?->name,
                    'ends_on' => $member->currentMembership ? format_date($member->currentMembership->ends_on) : null,
                    'days_left' => $member->currentMembership?->daysRemaining(),
                ],
            ]);
        }

        return $this->done(back(), $message);
    }

    public function checkout(Attendance $attendance): RedirectResponse
    {
        Gate::authorize(Permission::AttendanceManage);
        $this->attendance->checkOut($attendance);

        return $this->done(back(), "{$attendance->member->full_name} checked out.");
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        Gate::authorize(Permission::SettingsManage);
        $attendance->delete();

        return $this->done(back(), 'Visit removed.');
    }

    /**
     * Full-screen kiosk for QR scanners (USB/Bluetooth scanners type the code and press Enter).
     */
    public function kiosk(): View
    {
        return view('attendance.kiosk');
    }
}
