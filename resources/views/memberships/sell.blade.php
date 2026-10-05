@php
    $taxEnabled = (bool) tenant()->setting('tax.enabled');
    $taxRate = (float) tenant()->setting('tax.rate', 0);
    $planData = $plans->mapWithKeys(fn ($p) => [$p->id => ['name' => $p->name, 'price' => $p->effectivePrice(), 'fee' => (float) $p->signup_fee, 'taxable' => $p->is_taxable, 'duration' => $p->durationLabel(), 'unit' => $p->duration_unit->value, 'value' => $p->duration_value]]);
    $isFirst = $member ? $member->memberships()->doesntExist() : true;
    $symbol = config('gym.currencies.'.tenant()->currency.'.0');
@endphp
<x-layouts.app :title="$renewing ? 'Renew membership' : 'Sell membership'">
    <x-page-header :title="$renewing ? 'Renew membership' : 'Sell a membership'" :description="$renewing ? 'Renewing '.$renewing->plan->name.' for '.$member->full_name.' (current period ends '.format_date($renewing->ends_on).')' : 'Creates the membership, an invoice and optionally records payment.'">
        <x-slot:actions><x-button variant="secondary" icon="arrow-left" :href="$member ? route('members.show', $member) : route('memberships.index')">Back</x-button></x-slot:actions>
    </x-page-header>

    @if ($plans->isEmpty())
        <div class="card"><x-empty-state icon="sparkles" title="No plans on sale" description="Create a membership plan first."><x-button :href="route('plans.create')">Create plan</x-button></x-empty-state></div>
    @else
        <form method="POST" action="{{ $renewing ? route('memberships.renew.store', $renewing) : route('memberships.store') }}"
              x-data="{
                  plans: @js($planData), planId: @js((string) old('membership_plan_id', $renewing?->membership_plan_id ?? $plans->first()->id)),
                  discount: @js((float) old('discount_amount', 0)), pay: @js(old('payment_amount') !== null ? (float) old('payment_amount') : null),
                  firstTime: @js($isFirst && ! $renewing), taxEnabled: @js($taxEnabled), taxRate: @js($taxRate), symbol: @js($symbol),
                  get plan() { return this.plans[this.planId] },
                  get fee() { return this.firstTime ? this.plan.fee : 0 },
                  get base() { return Math.max(0, this.plan.price - Math.min(this.discount || 0, this.plan.price)) },
                  get tax() { return this.taxEnabled && this.plan.taxable ? Math.round((this.base + this.fee) * this.taxRate) / 100 : 0 },
                  get total() { return Math.round((this.base + this.fee + this.tax) * 100) / 100 },
                  fmt(v) { return this.symbol + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
              }" x-init="if (pay === null) pay = total" class="grid gap-6 lg:grid-cols-[1fr_340px]">
            @csrf
            <div class="space-y-6">
                @unless ($renewing)
                    <x-card title="Member">
                        <x-member-picker :member="$member" autofocus />
                    </x-card>
                @endunless

                <x-card title="Choose a plan">
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($plans as $plan)
                            <label class="relative flex cursor-pointer flex-col rounded-xl border p-4 transition"
                                   :class="planId === '{{ $plan->id }}' ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500 dark:bg-brand-500/10' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700'">
                                <input type="radio" name="membership_plan_id" value="{{ $plan->id }}" x-model="planId" class="sr-only">
                                <span class="flex items-center gap-2 text-sm font-semibold"><span class="size-2.5 rounded-full" style="background: {{ $plan->color }}"></span>{{ $plan->name }}</span>
                                <span class="mt-1 text-xs text-zinc-500">{{ $plan->durationLabel() }}</span>
                                <span class="mt-2 text-lg font-semibold">{{ money($plan->effectivePrice()) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('membership_plan_id')<p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </x-card>

                <x-card title="Details">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-input name="starts_on" type="date" label="Start date" :value="$startsOn" required />
                        <div class="space-y-1.5">
                            <label for="discount_amount" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Discount ({{ tenant()->currency }})</label>
                            <input id="discount_amount" type="number" step="0.01" min="0" name="discount_amount" x-model.number="discount" class="form-control">
                        </div>
                        <div class="sm:col-span-2"><x-textarea name="notes" label="Notes" rows="2" /></div>
                        <div class="sm:col-span-2"><x-toggle name="auto_renew" label="Flag for auto-renewal" description="Shown to staff when the membership is about to expire." /></div>
                    </div>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card title="Summary">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-zinc-500" x-text="plan.name + ' · ' + plan.duration"></dt><dd class="tabular-nums" x-text="fmt(plan.price)"></dd></div>
                        <div class="flex justify-between" x-show="discount > 0"><dt class="text-zinc-500">Discount</dt><dd class="tabular-nums" x-text="'-' + fmt(Math.min(discount, plan.price))"></dd></div>
                        <div class="flex justify-between" x-show="fee > 0"><dt class="text-zinc-500">Joining fee</dt><dd class="tabular-nums" x-text="fmt(fee)"></dd></div>
                        <div class="flex justify-between" x-show="tax > 0"><dt class="text-zinc-500">{{ tenant()->setting('tax.label', 'Tax') }} ({{ $taxRate }}%)</dt><dd class="tabular-nums" x-text="fmt(tax)"></dd></div>
                        <div class="flex justify-between border-t border-zinc-100 pt-2 text-base font-semibold dark:border-zinc-800"><dt>Total</dt><dd class="tabular-nums" x-text="fmt(total)"></dd></div>
                    </dl>
                </x-card>

                <x-card title="Take payment now" description="Leave at 0 to invoice and collect later.">
                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <label for="payment_amount" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Amount received</label>
                            <input id="payment_amount" type="number" step="0.01" min="0" name="payment_amount" x-model.number="pay" class="form-control">
                            <div class="flex gap-2 text-xs"><button type="button" class="font-semibold text-brand-600" @click="pay = total">Full amount</button><button type="button" class="text-zinc-500" @click="pay = 0">None</button></div>
                            @error('payment_amount')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <x-select name="payment_method" label="Method" :options="$methods" value="cash" />
                        <x-input name="payment_reference" label="Reference" placeholder="Receipt / transaction no." />
                    </div>
                    <x-slot:footer><x-button class="w-full" icon="check">{{ $renewing ? 'Renew membership' : 'Complete sale' }}</x-button></x-slot:footer>
                </x-card>
            </div>
        </form>
    @endif
</x-layouts.app>
