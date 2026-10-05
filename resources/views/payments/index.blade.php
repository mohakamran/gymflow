<x-layouts.app title="Payments">
    <x-page-header title="Payments" :description="'Collected '.money($total).' · '.\Carbon\Carbon::parse($filters['from'])->format('M j').' – '.\Carbon\Carbon::parse($filters['to'])->format('M j, Y')">
        @can(\App\Enums\Permission::PaymentsManage)
            <x-slot:actions><x-button icon="plus" :href="route('payments.create')">Record payment</x-button></x-slot:actions>
        @endcan
    </x-page-header>

    @if ($byMethod->isNotEmpty())
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($byMethod->take(4) as $method => $amount)
                <div class="card p-4"><p class="text-xs text-zinc-500">{{ $method }}</p><p class="mt-1 text-lg font-semibold tabular-nums">{{ money($amount) }}</p><p class="text-xs text-zinc-400">{{ $total > 0 ? round($amount / $total * 100) : 0 }}% of total</p></div>
            @endforeach
        </div>
    @endif

    <x-card :padding="false">
        <x-filters :reset="route('payments.index')">
            <x-input name="from" type="date" label="From" :value="$filters['from']" />
            <x-input name="to" type="date" label="To" :value="$filters['to']" />
            <x-select name="method" label="Method" :options="\App\Enums\PaymentMethod::options()" :value="$filters['method'] ?? null" placeholder="Any" />
            <x-select name="status" label="Status" :options="\App\Enums\PaymentStatus::options()" :value="$filters['status'] ?? null" placeholder="Any" />
            <x-input name="search" type="search" label="Search" :value="$filters['search'] ?? null" placeholder="Member or reference" />
        </x-filters>
        @if ($payments->isEmpty())
            <x-empty-state icon="card" title="No payments in this period" />
        @else
            <div class="overflow-x-auto">
                <table class="table-default">
                    <thead><tr><th>Date</th><th>Member</th><th class="hidden md:table-cell">Reference</th><th class="hidden md:table-cell">Invoice</th><th>Method</th><th class="text-right">Amount</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('payments.show', $payment) }}'">
                                <td class="whitespace-nowrap">{{ format_date($payment->paid_at, true) }}</td>
                                <td class="font-medium">{{ $payment->member->full_name }}</td>
                                <td class="hidden font-mono text-xs md:table-cell">{{ $payment->reference }}</td>
                                <td class="hidden md:table-cell">{{ $payment->invoice?->number ?? '—' }}</td>
                                <td>{{ $payment->method->label() }}</td>
                                <td class="text-right tabular-nums">{{ money($payment->amount) }}@if ((float) $payment->refunded_amount)<p class="text-xs text-rose-600">-{{ money($payment->refunded_amount) }}</p>@endif</td>
                                <td><x-status-badge :status="$payment->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $payments->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
