<x-layouts.app title="Payments & invoices">
    <x-page-header title="Payments & invoices" />
    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Invoices" :padding="false">
            @forelse ($invoices as $invoice)
                <div class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3 last:border-0 dark:border-zinc-800">
                    <div class="min-w-0 flex-1"><p class="font-mono text-sm font-medium">{{ $invoice->number }}</p><p class="text-xs text-zinc-500">{{ format_date($invoice->issued_on) }}@if ($invoice->status->isOpen()) · {{ money($invoice->balance(), $invoice->currency) }} due {{ format_date($invoice->due_on) }}@endif</p></div>
                    <span class="text-sm tabular-nums">{{ money($invoice->total, $invoice->currency) }}</span>
                    <x-status-badge :status="$invoice->status" />
                    <a href="{{ route('portal.invoices.pdf', $invoice) }}" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Download PDF"><x-icon name="download" class="size-4" /></a>
                </div>
            @empty
                <x-empty-state icon="clipboard" title="No invoices" />
            @endforelse
        </x-card>
        <x-card title="Payments" :padding="false">
            @forelse ($payments as $payment)
                <div class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3 last:border-0 dark:border-zinc-800">
                    <div class="min-w-0 flex-1"><p class="text-sm font-medium">{{ $payment->method->label() }}</p><p class="text-xs text-zinc-500">{{ format_date($payment->paid_at) }} · {{ $payment->reference }}</p></div>
                    <span class="text-sm tabular-nums">{{ money($payment->amount) }}</span>
                    <x-status-badge :status="$payment->status" />
                </div>
            @empty
                <x-empty-state icon="card" title="No payments" />
            @endforelse
        </x-card>
    </div>
</x-layouts.app>
