<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class MembershipService
{
    public function __construct(protected InvoiceService $invoices, protected PaymentService $payments) {}

    /**
     * Sell a plan to a member: creates the membership, its invoice and (optionally) a payment.
     *
     * @param  array{starts_on?: string|null, discount_amount?: float|int|string|null, notes?: string|null, auto_renew?: bool, renewed_from_id?: int|null, payment_amount?: float|int|string|null, payment_method?: string|null, payment_reference?: string|null}  $options
     * @return array{membership: Membership, invoice: Invoice, payment: Payment|null}
     */
    public function sell(Member $member, MembershipPlan $plan, array $options = []): array
    {
        if (! $plan->is_active) {
            throw new BusinessRuleException("The {$plan->name} plan is no longer on sale.");
        }

        if ($member->status === MemberStatus::Suspended) {
            throw new BusinessRuleException('This member is suspended. Reactivate them before selling a membership.');
        }

        $startsOn = CarbonImmutable::parse($options['starts_on'] ?? $this->suggestedStartDate($member));
        $endsOn = $plan->duration_unit->endDate($startsOn, $plan->duration_value);
        $price = $plan->effectivePrice();
        $discount = min($price, round((float) ($options['discount_amount'] ?? 0), 2));

        $result = DB::transaction(function () use ($member, $plan, $options, $startsOn, $endsOn, $price, $discount): array {
            $membership = new Membership([
                'member_id' => $member->id,
                'membership_plan_id' => $plan->id,
                'renewed_from_id' => $options['renewed_from_id'] ?? null,
                'starts_on' => $startsOn->toDateString(),
                'ends_on' => $endsOn->toDateString(),
                'status' => $startsOn->gt(tenant_today()) ? MembershipStatus::Pending : MembershipStatus::Active,
                'price' => $price,
                'discount_amount' => $discount,
                'auto_renew' => (bool) ($options['auto_renew'] ?? false),
                'notes' => $options['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
            $membership->tenant_id = $member->tenant_id;
            $membership->save();

            $invoice = $this->invoices->createForMembership($membership);

            return ['membership' => $membership, 'invoice' => $invoice];
        });

        $payment = null;
        $amount = round((float) ($options['payment_amount'] ?? 0), 2);

        if ($amount > 0 && $result['invoice']->status->isOpen()) {
            $payment = $this->payments->record(
                $member,
                min($amount, $result['invoice']->balance()),
                PaymentMethod::from($options['payment_method'] ?? PaymentMethod::Cash->value),
                $result['invoice'],
                ['reference' => $options['payment_reference'] ?? null],
            );
        }

        return $result + ['payment' => $payment];
    }

    /**
     * Renew onto the same (or another) plan, starting the day after the current period ends.
     *
     * @param  array<string, mixed>  $options
     * @return array{membership: Membership, invoice: Invoice, payment: Payment|null}
     */
    public function renew(Membership $membership, ?MembershipPlan $plan = null, array $options = []): array
    {
        $membership->loadMissing('member', 'plan');
        $nextStart = CarbonImmutable::parse($membership->ends_on)->addDay();
        $startsOn = $nextStart->lt(tenant_today()) ? tenant_today() : $nextStart;

        return $this->sell($membership->member, $plan ?? $membership->plan, array_merge($options, [
            'starts_on' => $options['starts_on'] ?? $startsOn->toDateString(),
            'renewed_from_id' => $membership->id,
        ]));
    }

    public function suspend(Membership $membership): Membership
    {
        if ($membership->status !== MembershipStatus::Active) {
            throw new BusinessRuleException('Only active memberships can be frozen.');
        }

        $membership->status = MembershipStatus::Suspended;
        $membership->suspended_on = tenant_today();
        $membership->save();

        return $membership;
    }

    /**
     * Unfreeze a membership and push the end date back by the number of frozen days.
     */
    public function resume(Membership $membership): Membership
    {
        if ($membership->status !== MembershipStatus::Suspended) {
            throw new BusinessRuleException('This membership is not frozen.');
        }

        $frozenDays = (int) $membership->suspended_on->diffInDays(tenant_today());
        $membership->ends_on = $membership->ends_on->addDays($frozenDays);
        $membership->status = MembershipStatus::Active;
        $membership->suspended_on = null;
        $membership->save();

        return $membership;
    }

    public function cancel(Membership $membership): Membership
    {
        if (in_array($membership->status, [MembershipStatus::Cancelled, MembershipStatus::Expired], true)) {
            throw new BusinessRuleException('This membership has already ended.');
        }

        $membership->status = MembershipStatus::Cancelled;
        $membership->cancelled_on = tenant_today();
        $membership->save();

        return $membership;
    }

    /**
     * Activate memberships whose start date has arrived and expire those that have ended.
     * Runs for the active tenant (the scheduler loops over gyms).
     *
     * @return array{activated: int, expired: int}
     */
    public function refreshStatuses(): array
    {
        $today = tenant_today()->toDateString();
        $activated = 0;
        $expired = 0;

        Membership::query()->where('status', MembershipStatus::Pending->value)->where('starts_on', '<=', $today)
            ->each(function (Membership $membership) use (&$activated): void {
                $membership->update(['status' => MembershipStatus::Active]);
                $activated++;
            });

        Membership::query()->where('status', MembershipStatus::Active->value)->where('ends_on', '<', $today)
            ->each(function (Membership $membership) use (&$expired): void {
                $membership->update(['status' => MembershipStatus::Expired]);
                $expired++;
            });

        return ['activated' => $activated, 'expired' => $expired];
    }

    /**
     * New memberships start today, or the day after the member's current membership ends.
     */
    public function suggestedStartDate(Member $member): string
    {
        $current = $member->memberships()
            ->whereIn('status', [MembershipStatus::Active->value, MembershipStatus::Pending->value])
            ->max('ends_on');

        if ($current && CarbonImmutable::parse($current)->gte(tenant_today())) {
            return CarbonImmutable::parse($current)->addDay()->toDateString();
        }

        return tenant_today()->toDateString();
    }
}
