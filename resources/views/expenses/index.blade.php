<x-layouts.app title="Expenses">
    <x-page-header title="Expenses" :description="\Carbon\Carbon::parse($filters['from'])->format('M j').' – '.\Carbon\Carbon::parse($filters['to'])->format('M j, Y')">
        <x-slot:actions><x-button icon="plus" :href="route('expenses.create')">Record expense</x-button></x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="Expenses" :value="money($total)" icon="receipt" />
        <x-stat label="Revenue" :value="money($revenue)" icon="chart" hint="Payments in the same period" />
        <x-stat label="Profit" :value="money($revenue - $total)" icon="sparkles" :hint="$revenue > 0 ? round(($revenue - $total) / $revenue * 100, 1).'% margin' : null" />
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <x-card :padding="false">
            <x-filters :reset="route('expenses.index')">
                <x-input name="from" type="date" label="From" :value="$filters['from']" />
                <x-input name="to" type="date" label="To" :value="$filters['to']" />
                <x-select name="category" label="Category" :options="\App\Enums\ExpenseCategory::options()" :value="$filters['category'] ?? null" placeholder="All" />
                <x-input name="search" type="search" label="Search" :value="$filters['search'] ?? null" />
            </x-filters>
            @if ($expenses->isEmpty())
                <x-empty-state icon="receipt" title="No expenses recorded" description="Track rent, utilities, salaries and more to see your real profit."><x-button icon="plus" :href="route('expenses.create')">Record expense</x-button></x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="table-default">
                        <thead><tr><th>Date</th><th>Description</th><th>Category</th><th class="text-right">Amount</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($expenses as $expense)
                                <tr>
                                    <td class="whitespace-nowrap">{{ format_date($expense->spent_on) }}</td>
                                    <td><p class="font-medium">{{ $expense->title }}</p><p class="text-xs text-zinc-500">{{ $expense->vendor }}</p></td>
                                    <td><x-badge>{{ $expense->category->label() }}</x-badge></td>
                                    <td class="text-right tabular-nums">{{ money($expense->amount) }}</td>
                                    <td class="text-right whitespace-nowrap">
                                        @if ($expense->receipt_path)<x-button size="sm" variant="ghost" icon="download" :href="route('expenses.receipt', $expense)">Receipt</x-button>@endif
                                        <x-button size="sm" variant="ghost" :href="route('expenses.edit', $expense)">Edit</x-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $expenses->links() }}</div>
            @endif
        </x-card>
        <x-card title="By category">
            <x-chart title="Expenses by category" height="h-64" :config="['type' => 'hbar', 'labels' => $byCategory->keys()->map(fn ($c) => \App\Enums\ExpenseCategory::from($c)->label())->all(), 'format' => 'money', 'labelHeading' => 'Category', 'series' => [['label' => 'Spent', 'data' => $byCategory->values()->map(fn ($v) => (float) $v)->all()]]]" />
        </x-card>
    </div>
</x-layouts.app>
