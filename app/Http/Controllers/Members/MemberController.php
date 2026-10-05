<?php

namespace App\Http\Controllers\Members;

use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Members\MemberRequest;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\ProgressRecord;
use App\Models\User;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function __construct(protected MemberService $members) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Member::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(MemberStatus::class)],
            'membership' => ['nullable', Rule::in(['active', 'expiring', 'expired', 'none'])],
            'plan' => ['nullable', 'integer'],
            'trainer' => ['nullable', 'integer'],
            'sort' => ['nullable', Rule::in(['name', 'newest', 'oldest', 'code'])],
        ]);

        $user = $request->user();
        $today = tenant_today()->toDateString();
        $soon = tenant_today()->addDays(7)->toDateString();
        $activeStatuses = [MembershipStatus::Active->value];

        $members = Member::query()
            ->with(['currentMembership.plan', 'trainer'])
            ->when(! $user->can(Permission::MembersManage), fn ($query) => $query->where('trainer_id', $user->id))
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['trainer'] ?? null, fn ($query, $trainer) => $query->where('trainer_id', $trainer))
            ->when($filters['plan'] ?? null, fn ($query, $plan) => $query->whereHas('memberships', fn ($inner) => $inner->whereIn('status', $activeStatuses)->where('membership_plan_id', $plan)))
            ->when($filters['membership'] ?? null, function ($query, string $state) use ($today, $soon, $activeStatuses) {
                return match ($state) {
                    'active' => $query->whereHas('memberships', fn ($inner) => $inner->whereIn('status', $activeStatuses)->where('starts_on', '<=', $today)->where('ends_on', '>=', $today)),
                    'expiring' => $query->whereHas('memberships', fn ($inner) => $inner->whereIn('status', $activeStatuses)->whereBetween('ends_on', [$today, $soon])),
                    'expired' => $query->whereHas('memberships')->whereDoesntHave('memberships', fn ($inner) => $inner->whereIn('status', [...$activeStatuses, MembershipStatus::Pending->value, MembershipStatus::Suspended->value])->where('ends_on', '>=', $today)),
                    'none' => $query->whereDoesntHave('memberships'),
                };
            })
            ->when($filters['sort'] ?? 'newest', fn ($query, $sort) => match ($sort) {
                'name' => $query->orderBy('first_name')->orderBy('last_name'),
                'oldest' => $query->orderBy('joined_on')->orderBy('id'),
                'code' => $query->orderBy('member_code'),
                default => $query->latest('joined_on')->latest('id'),
            })
            ->paginate(20)
            ->withQueryString();

        return view('members.index', [
            'members' => $members,
            'filters' => $filters,
            'plans' => MembershipPlan::query()->orderBy('name')->pluck('name', 'id'),
            'trainers' => $this->trainerOptions(),
            'counts' => [
                'all' => Member::query()->count(),
                'active' => Member::query()->where('status', MemberStatus::Active->value)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Member::class);

        return view('members.form', ['member' => new Member(['joined_on' => tenant_today(), 'status' => MemberStatus::Active]), 'trainers' => $this->trainerOptions()]);
    }

    public function store(MemberRequest $request): RedirectResponse
    {
        $member = $this->members->create(tenant(), $request->memberData(), $request->file('photo'));

        return $this->done(redirect()->route('members.show', $member), "{$member->full_name} added. Next, sell them a membership.");
    }

    public function show(Member $member): View
    {
        $this->authorize('view', $member);

        $member->load([
            'trainer', 'user', 'currentMembership.plan',
            'memberships' => fn ($query) => $query->with('plan')->latest('starts_on')->latest('id'),
            'invoices' => fn ($query) => $query->latest('issued_on')->latest('id')->limit(20),
            'payments' => fn ($query) => $query->latest('paid_at')->limit(20),
            'attendances' => fn ($query) => $query->latest('checked_in_at')->limit(20),
            'workoutPlans' => fn ($query) => $query->with('trainer')->latest(),
            'progressRecords' => fn ($query) => $query->orderBy('recorded_on'),
            'memberNotes' => fn ($query) => $query->with('author')->latest(),
            'documents' => fn ($query) => $query->latest(),
            'bookings' => fn ($query) => $query->with('session.gymClass')->latest()->limit(10),
        ]);

        return view('members.show', [
            'member' => $member,
            'visitsThisMonth' => $member->attendances()->betweenDays(tenant_today()->startOfMonth()->toDateString(), tenant_today()->toDateString())->count(),
            'totalVisits' => $member->attendances()->count(),
            'outstanding' => round($member->invoices()->open()->get()->sum(fn ($invoice) => $invoice->balance()), 2),
            'lifetimeValue' => round($member->payments()->successful()->get()->sum(fn ($payment) => $payment->netAmount()), 2),
            'measurements' => ProgressRecord::MEASUREMENTS,
        ]);
    }

    public function edit(Member $member): View
    {
        $this->authorize('update', $member);

        return view('members.form', ['member' => $member, 'trainers' => $this->trainerOptions()]);
    }

    public function update(MemberRequest $request, Member $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $this->members->update($member, $request->memberData(), $request->file('photo'), $request->boolean('remove_photo'));

        return $this->done(redirect()->route('members.show', $member), 'Member updated.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        $this->members->revokePortalAccess($member);
        $member->delete();

        return $this->done(redirect()->route('members.index'), "{$member->full_name} was archived.");
    }

    public function card(Member $member): View
    {
        $this->authorize('view', $member);

        return view('members.card', ['member' => $member->load('currentMembership.plan')]);
    }

    public function invite(Member $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $this->members->invitePortalAccess($member);

        return $this->done(back(), "Portal invitation sent to {$member->email}.");
    }

    public function revoke(Member $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $this->members->revokePortalAccess($member);

        return $this->done(back(), 'Portal access removed.');
    }

    /**
     * Lightweight JSON search used by member pickers (check-in, payments, bookings).
     */
    public function lookup(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Member::class);

        $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        $members = Member::query()
            ->search($request->string('q')->toString())
            ->with('currentMembership.plan')
            ->orderBy('first_name')
            ->limit(8)
            ->get()
            ->map(fn (Member $member): array => [
                'id' => $member->id,
                'name' => $member->full_name,
                'code' => $member->member_code,
                'email' => $member->email,
                'initials' => $member->initials,
                'photo' => $member->photo_url,
                'plan' => $member->currentMembership?->plan?->name,
                'status' => $member->currentMembership?->displayStatus()['label'] ?? 'No membership',
            ]);

        return response()->json($members);
    }

    /**
     * @return Collection<int, string>
     */
    protected function trainerOptions(): Collection
    {
        return User::query()->where('is_active', true)->role(Role::Trainer->value)->orderBy('name')->pluck('name', 'id');
    }
}
