<x-layouts.app title="Record payment">
    <x-page-header title="Record a payment" description="Apply it to an open invoice, or record it on account.">
        <x-slot:actions><x-button variant="secondary" icon="arrow-left" :href="route('payments.index')">Back</x-button></x-slot:actions>
    </x-page-header>

    @if (! $member)
        <form method="GET" action="{{ route('payments.create') }}" class="max-w-xl">
            <x-card title="Who is paying?">
                <div x-data @member-selected.window="$nextTick(() => $el.closest('form').submit())"><x-member-picker name="member" autofocus /></div>
            </x-card>
        </form>
    @else
        <form method="POST" action="{{ route('payments.store') }}" class="grid max-w-4xl gap-6 lg:grid-cols-2" x-data="{ invoice: @js((string) old('invoice_id', $invoice?->id ?? '')), balances: @js($openInvoices->mapWithKeys(fn ($i) => [$i->id => $i->balance()])), amount: @js(old('amount', $invoice?->balance())) }">
            @csrf
            <input type="hidden" name="member_id" value="{{ $member->id }}">
            <x-card title="Apply to">
                <p class="mb-4 text-sm">Paying: <a href="{{ route('members.show', $member) }}" class="font-semibold hover:text-brand-600">{{ $member->full_name }}</a> <a href="{{ route('payments.create') }}" class="ml-2 text-xs text-brand-600">change</a></p>
                <div class="space-y-2">
                    @foreach ($openInvoices as $open)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 text-sm" :class="invoice === '{{ $open->id }}' ? 'border-brand-500 ring-1 ring-brand-500' : 'border-zinc-200 dark:border-zinc-700'">
                            <input type="radio" name="invoice_id" value="{{ $open->id }}" x-model="invoice" @change="amount = balances[invoice]" class="form-checkbox rounded-full">
                            <span class="flex-1"><span class="font-mono font-medium">{{ $open->number }}</span><span class="block text-xs text-zinc-500">Issued {{ format_date($open->issued_on) }}</span></span>
                            <span class="tabular-nums">{{ money($open->balance()) }} due</span>
                        </label>
                    @endforeach
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 text-sm" :class="invoice === '' ? 'border-brand-500 ring-1 ring-brand-500' : 'border-zinc-200 dark:border-zinc-700'">
                        <input type="radio" name="invoice_id" value="" x-model="invoice" class="form-checkbox rounded-full">
                        <span>No invoice (on account)</span>
                    </label>
                </div>
            </x-card>
            <x-card title="Payment">
                <div class="space-y-4">
                    <div class="space-y-1.5">
                        <label for="amount" class="block text-sm font-medium">Amount ({{ tenant()->currency }}) <span class="text-rose-500">*</span></label>
                        <input id="amount" type="number" name="amount" step="0.01" min="0.01" x-model="amount" required class="form-control @error('amount') is-invalid @enderror">
                        @error('amount')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <x-select name="method" label="Method" :options="$methods" value="cash" required />
                    <x-input name="paid_at" type="datetime-local" label="Paid at" :value="tenant_now()->format('Y-m-d\TH:i')" />
                    <x-input name="reference" label="Reference" placeholder="Receipt / transaction no." />
                    <x-textarea name="notes" label="Notes" rows="2" />
                </div>
                <x-slot:footer><x-button icon="check">Record payment</x-button></x-slot:footer>
            </x-card>
        </form>
    @endif
</x-layouts.app>
