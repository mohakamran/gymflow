<x-layouts.app :title="'Payment '.$payment->reference">
    <div class="mb-4"><a href="{{ route('payments.index') }}" class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200"><x-icon name="arrow-left" class="size-4" /> Payments</a></div>
    <x-page-header :title="money($payment->amount)" :description="'Payment '.$payment->reference" />

    <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <x-card title="Details">
            <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                <div><dt class="text-zinc-500">Member</dt><dd class="mt-0.5 font-medium"><a href="{{ route('members.show', $payment->member) }}" class="hover:text-brand-600">{{ $payment->member->full_name }}</a></dd></div>
                <div><dt class="text-zinc-500">Status</dt><dd class="mt-0.5"><x-status-badge :status="$payment->status" /></dd></div>
                <div><dt class="text-zinc-500">Paid at</dt><dd class="mt-0.5 font-medium">{{ format_date($payment->paid_at, true) }}</dd></div>
                <div><dt class="text-zinc-500">Method</dt><dd class="mt-0.5 font-medium">{{ $payment->method->label() }} <span class="text-xs text-zinc-400">via {{ $payment->gateway }}</span></dd></div>
                <div><dt class="text-zinc-500">Invoice</dt><dd class="mt-0.5 font-medium">@if ($payment->invoice)<a href="{{ route('invoices.show', $payment->invoice) }}" class="hover:text-brand-600">{{ $payment->invoice->number }}</a>@else On account @endif</dd></div>
                <div><dt class="text-zinc-500">External reference</dt><dd class="mt-0.5 font-medium">{{ $payment->gateway_reference ?? '—' }}</dd></div>
                <div><dt class="text-zinc-500">Received by</dt><dd class="mt-0.5 font-medium">{{ $payment->receiver?->name ?? '—' }}</dd></div>
                <div><dt class="text-zinc-500">Refunded</dt><dd class="mt-0.5 font-medium">{{ money($payment->refunded_amount) }}</dd></div>
                @if ($payment->notes)<div class="sm:col-span-2"><dt class="text-zinc-500">Notes</dt><dd class="mt-0.5 whitespace-pre-line">{{ $payment->notes }}</dd></div>@endif
            </dl>
        </x-card>

        @if ($payment->refundableAmount() > 0 && in_array($payment->status, [\App\Enums\PaymentStatus::Completed, \App\Enums\PaymentStatus::PartiallyRefunded], true))
            @can('update', $payment)
                <form method="POST" action="{{ route('payments.refund', $payment) }}">
                    @csrf
                    <x-card title="Refund" :description="'Up to '.money($payment->refundableAmount()).' can be refunded.'">
                        <div class="space-y-4">
                            <x-input name="amount" type="number" step="0.01" min="0.01" :max="$payment->refundableAmount()" label="Amount" :value="$payment->refundableAmount()" required />
                            <x-input name="reason" label="Reason" placeholder="Optional" />
                        </div>
                        <x-slot:footer><x-button variant="danger">Refund</x-button></x-slot:footer>
                    </x-card>
                </form>
            @endcan
        @endif
    </div>
</x-layouts.app>
