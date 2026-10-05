<x-layouts.app :title="'Invoice '.$invoice->number">
    <div class="mb-4"><a href="{{ route('invoices.index') }}" class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200"><x-icon name="arrow-left" class="size-4" /> Invoices</a></div>
    <x-page-header :title="'Invoice '.$invoice->number" :description="$invoice->member->full_name.($invoice->sent_at ? ' · emailed '.$invoice->sent_at->diffForHumans() : '')">
        <x-slot:actions>
            <x-button variant="secondary" icon="printer" :href="route('invoices.print', $invoice)" target="_blank">Print</x-button>
            <x-button variant="secondary" icon="download" :href="route('invoices.pdf', $invoice)">PDF</x-button>
            @can('update', $invoice)
                <form method="POST" action="{{ route('invoices.email', $invoice) }}">@csrf<x-button variant="secondary" icon="mail" :disabled="! $invoice->member->email">Email</x-button></form>
                @if ($invoice->status->isOpen() && (float) $invoice->amount_paid == 0)
                    <x-confirm :action="route('invoices.void', $invoice)" title="Void this invoice?" message="The invoice stays on record but no longer counts as owed. This can't be undone." trigger="Void" confirm="Void invoice" size="md" triggerVariant="secondary" />
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <div class="rounded-2xl bg-white p-6 shadow-xs ring-1 ring-zinc-200 sm:p-10 dark:ring-zinc-800">
            @include('invoices._document')
        </div>

        <div class="space-y-6">
            @if ($invoice->status->isOpen() && auth()->user()->can(\App\Enums\Permission::PaymentsManage))
                <form method="POST" action="{{ route('payments.store') }}">
                    @csrf
                    <input type="hidden" name="member_id" value="{{ $invoice->member_id }}">
                    <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                    <x-card title="Record payment" :description="'Balance due: '.money($invoice->balance(), $invoice->currency)">
                        <div class="space-y-4">
                            <x-input name="amount" type="number" step="0.01" min="0.01" :max="$invoice->balance()" label="Amount" :value="$invoice->balance()" required />
                            <x-select name="method" label="Method" :options="$methods" value="cash" required />
                            <x-input name="reference" label="Reference" placeholder="Optional" />
                        </div>
                        <x-slot:footer><x-button class="w-full" icon="check">Record payment</x-button></x-slot:footer>
                    </x-card>
                </form>
            @endif

            <x-card title="Payments" :padding="false">
                @forelse ($invoice->payments as $payment)
                    <a href="{{ route('payments.show', $payment) }}" class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/40">
                        <div class="min-w-0 flex-1"><p class="text-sm font-medium">{{ money($payment->amount, $invoice->currency) }} · {{ $payment->method->label() }}</p><p class="text-xs text-zinc-500">{{ format_date($payment->paid_at, true) }} · {{ $payment->receiver?->name }}</p></div>
                        <x-status-badge :status="$payment->status" />
                    </a>
                @empty
                    <p class="px-5 py-4 text-sm text-zinc-500">No payments yet.</p>
                @endforelse
            </x-card>

            @if ($invoice->membership)
                <x-card title="Membership">
                    <p class="text-sm font-medium">{{ $invoice->membership->plan->name }}</p>
                    <p class="text-xs text-zinc-500">{{ format_date($invoice->membership->starts_on) }} – {{ format_date($invoice->membership->ends_on) }}</p>
                    <x-button size="sm" variant="ghost" class="mt-2 -ml-2" :href="route('members.show', $invoice->member_id)">View member</x-button>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.app>
