<x-layouts.app title="Invoices">
    <x-page-header title="Invoices" description="Every membership sale creates an invoice automatically.">
        @can(\App\Enums\Permission::InvoicesManage)
            <x-slot:actions><x-button icon="plus" :href="route('invoices.create')">New invoice</x-button></x-slot:actions>
        @endcan
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="Outstanding" :value="money($summary['outstanding'])" icon="clipboard" hint="Unpaid + partially paid" />
        <x-stat label="Overdue" :value="money($summary['overdue'])" icon="exclamation" :hint="$summary['overdueCount'].' invoices past due'" />
        <x-stat label="Paid this month" :value="money($summary['paidThisMonth'])" icon="check-circle" hint="Invoices issued & fully paid" />
    </div>

    <x-card :padding="false">
        <x-filters :reset="route('invoices.index')">
            <div class="relative min-w-56 flex-1">
                <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-zinc-400" />
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Invoice number or member" class="form-control pl-9" aria-label="Search">
            </div>
            <select name="status" class="form-control w-auto" aria-label="Status">
                <option value="">Any status</option>
                <option value="overdue" @selected(($filters['status'] ?? '') === 'overdue')>Overdue</option>
                @foreach (\App\Enums\InvoiceStatus::options() as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control w-auto" aria-label="From">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control w-auto" aria-label="To">
        </x-filters>
        @if ($invoices->isEmpty())
            <x-empty-state icon="clipboard" title="No invoices found" />
        @else
            <div class="overflow-x-auto">
                <table class="table-default">
                    <thead><tr><th>Invoice</th><th>Member</th><th class="hidden md:table-cell">Issued</th><th class="hidden md:table-cell">Due</th><th class="text-right">Total</th><th class="text-right">Balance</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('invoices.show', $invoice) }}'">
                                <td><a href="{{ route('invoices.show', $invoice) }}" class="font-mono font-medium text-zinc-900 hover:text-brand-600 dark:text-white">{{ $invoice->number }}</a></td>
                                <td>{{ $invoice->member->full_name }}</td>
                                <td class="hidden whitespace-nowrap md:table-cell">{{ format_date($invoice->issued_on) }}</td>
                                <td @class(['hidden whitespace-nowrap md:table-cell', 'font-medium text-rose-600' => $invoice->isOverdue()])>{{ format_date($invoice->due_on) }}</td>
                                <td class="text-right tabular-nums">{{ money($invoice->total, $invoice->currency) }}</td>
                                <td class="text-right tabular-nums">{{ $invoice->status->isOpen() ? money($invoice->balance(), $invoice->currency) : '—' }}</td>
                                <td>@if ($invoice->isOverdue())<x-badge color="rose">Overdue</x-badge>@else<x-status-badge :status="$invoice->status" />@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $invoices->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
