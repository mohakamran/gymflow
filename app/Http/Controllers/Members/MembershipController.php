<?php

namespace App\Http\Controllers\Members;

use App\Enums\MembershipStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Members\SellMembershipRequest;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Notifications\MembershipRenewedNotification;
use App\Services\MembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function __construct(protected MembershipService $memberships) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Membership::class);

        $filters = $request->validate([
            'state' => ['nullable', Rule::in(['active', 'expiring', 'expired', 'suspended', 'pending', 'cancelled'])],
            'plan' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $state = $filters['state'] ?? 'active';

        $memberships = Membership::query()
            ->with(['member', 'plan', 'invoice'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas('member', fn ($inner) => $inner->search($search)))
            ->when($filters['plan'] ?? null, fn ($query, $plan) => $query->where('membership_plan_id', $plan))
            ->when($state === 'expiring', fn ($query) => $query->expiringSoon(), fn ($query) => $query->where('status', $state))
            ->orderBy($state === 'expired' || $state === 'cancelled' ? 'ends_on' : 'ends_on', $state === 'expired' ? 'desc' : 'asc')
            ->paginate(25)
            ->withQueryString();

        $counts = Membership::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('memberships.index', [
            'memberships' => $memberships,
            'filters' => $filters,
            'state' => $state,
            'counts' => $counts->put('expiring', Membership::query()->expiringSoon()->count()),
            'plans' => MembershipPlan::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Membership::class);

        $member = $request->integer('member') ? Member::findOrFail($request->integer('member')) : null;

        return view('memberships.sell', [
            'member' => $member,
            'renewing' => null,
            'plans' => MembershipPlan::query()->active()->orderBy('sort_order')->orderBy('price')->get(),
            'startsOn' => $member ? $this->memberships->suggestedStartDate($member) : tenant_today()->toDateString(),
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function store(SellMembershipRequest $request): RedirectResponse
    {
        $member = Member::findOrFail($request->integer('member_id'));
        $plan = MembershipPlan::findOrFail($request->integer('membership_plan_id'));

        $result = $this->memberships->sell($member, $plan, $request->validated());
        $this->notifyMember($result['membership']);

        return $this->done(
            redirect()->route('members.show', $member),
            "{$plan->name} membership sold. Invoice {$result['invoice']->number}".($result['payment'] ? ' paid '.money($result['payment']->amount).'.' : ' is awaiting payment.'),
        );
    }

    public function renewForm(Membership $membership): View
    {
        $this->authorize('update', $membership);
        $membership->load('member', 'plan');

        return view('memberships.sell', [
            'member' => $membership->member,
            'renewing' => $membership,
            'plans' => MembershipPlan::query()->active()->orderBy('sort_order')->orderBy('price')->get(),
            'startsOn' => $this->memberships->suggestedStartDate($membership->member),
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function renew(SellMembershipRequest $request, Membership $membership): RedirectResponse
    {
        $this->authorize('update', $membership);

        $plan = MembershipPlan::findOrFail($request->integer('membership_plan_id'));
        $result = $this->memberships->renew($membership, $plan, $request->validated());
        $this->notifyMember($result['membership']);

        return $this->done(redirect()->route('members.show', $membership->member_id), 'Membership renewed until '.format_date($result['membership']->ends_on).'.');
    }

    public function suspend(Membership $membership): RedirectResponse
    {
        $this->authorize('update', $membership);
        $this->memberships->suspend($membership);

        return $this->done(back(), 'Membership frozen. The end date will be extended when it is resumed.');
    }

    public function resume(Membership $membership): RedirectResponse
    {
        $this->authorize('update', $membership);
        $this->memberships->resume($membership);

        return $this->done(back(), 'Membership resumed. New end date: '.format_date($membership->ends_on).'.');
    }

    public function cancel(Membership $membership): RedirectResponse
    {
        $this->authorize('update', $membership);
        $this->memberships->cancel($membership);

        return $this->done(back(), 'Membership cancelled.');
    }

    protected function notifyMember(Membership $membership): void
    {
        $membership->loadMissing('member', 'plan');

        if ($membership->member->email && $membership->status !== MembershipStatus::Cancelled) {
            $membership->member->notify(new MembershipRenewedNotification(tenant(), $membership));
        }
    }
}
