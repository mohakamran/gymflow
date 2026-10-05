<x-layouts.app :title="$report['title'].' report'">
    @php
        $period = $report['period'];
        $query = array_filter($filters, fn ($v) => $v !== null && $v !== '');
        $presets = [
            'Last 7 days' => [tenant_today()->subDays(6), tenant_today()],
            'Last 30 days' => [tenant_today()->subDays(29), tenant_today()],
            'This month' => [tenant_today()->startOfMonth(), tenant_today()],
            'Last month' => [tenant_today()->subMonthNoOverflow()->startOfMonth(), tenant_today()->subMonthNoOverflow()->endOfMonth()],
            'This year' => [tenant_today()->startOfYear(), tenant_today()],
            'Last 12 months' => [tenant_today()->subMonthsNoOverflow(11)->startOfMonth(), tenant_today()],
        ];
        $filterLabels = ['method' => 'Payment method', 'payment_status' => 'Status', 'category' => 'Category', 'plan' => 'Plan', 'trainer' => 'Trainer'];
    @endphp
    <div class="mb-4"><a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200"><x-icon name="arrow-left" class="size-4" /> Reports</a></div>
    <x-page-header :title="$report['title']" :description="$report['description'].' '.$period->label()">
        <x-slot:actions>
            <x-button variant="secondary" size="sm" icon="download" :href="route('reports.export', [$report['type'], 'csv'] + $query)">CSV / Excel</x-button>
            <x-button variant="secondary" size="sm" icon="download" :href="route('reports.export', [$report['type'], 'pdf'] + $query)">PDF</x-button>
            <x-button variant="secondary" size="sm" icon="printer" :href="route('reports.export', [$report['type'], 'print'] + $query)" target="_blank">Print</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false" class="mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-3 p-4">
            <x-input name="from" type="date" label="From" :value="$period->from->toDateString()" />
            <x-input name="to" type="date" label="To" :value="$period->to->toDateString()" />
            @foreach (\App\Services\Reporting\ReportService::TYPES[$report['type']]['filters'] as $filter)
                <x-select :name="$filter" :label="$filterLabels[$filter]" :options="$options[$filter]" :value="$filters[$filter] ?? null" placeholder="All" />
            @endforeach
            <x-button>Apply</x-button>
        </form>
        <div class="flex flex-wrap gap-2 border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
            @foreach ($presets as $label => [$from, $to])
                @php $active = $period->from->isSameDay($from) && $period->to->isSameDay($to); @endphp
                <a href="{{ route('reports.show', [$report['type'], 'from' => $from->toDateString(), 'to' => $to->toDateString()] + \Illuminate\Support\Arr::except($query, ['from', 'to'])) }}"
                   @class(['rounded-full px-3 py-1 text-xs font-medium', 'bg-brand-600 text-white' => $active, 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300' => ! $active])>{{ $label }}</a>
            @endforeach
        </div>
    </x-card>

    @if ($report['summary'])
        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($report['summary'] as [$label, $value])
                <div class="card p-5"><p class="text-sm text-zinc-500">{{ $label }}</p><p class="mt-1 text-2xl font-semibold tracking-tight tabular-nums">{{ $value }}</p></div>
            @endforeach
        </div>
    @endif

    @if ($report['chart'])
        <x-card class="mb-6"><x-chart :title="$report['title']" :config="$report['chart'] + ['labelHeading' => 'Label']" height="h-72" /></x-card>
    @endif

    <x-card :padding="false">
        @if (empty($report['rows']))
            <x-empty-state icon="chart" title="No data for this period" />
        @else
            <div class="max-h-[640px] overflow-auto">
                <table class="table-default">
                    <thead class="sticky top-0 bg-white dark:bg-zinc-900"><tr>@foreach ($report['columns'] as $key => $label)<th @class(['text-right' => isset($report['formats'][$key])])>{{ $label }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($report['rows'] as $row)
                            <tr>@foreach ($report['columns'] as $key => $label)<td @class(['whitespace-nowrap', 'text-right tabular-nums' => isset($report['formats'][$key])])>{{ \App\Services\Reporting\ReportService::cell($row[$key] ?? null, $report['formats'][$key] ?? null) }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                    @if ($report['totals'])
                        <tfoot class="border-t-2 border-zinc-200 font-semibold dark:border-zinc-700"><tr>@foreach ($report['columns'] as $key => $label)<td @class(['px-4 py-3', 'text-right tabular-nums' => isset($report['formats'][$key])])>{{ \App\Services\Reporting\ReportService::cell($report['totals'][$key] ?? '', $report['formats'][$key] ?? null) }}</td>@endforeach</tr></tfoot>
                    @endif
                </table>
            </div>
        @endif
    </x-card>
</x-layouts.app>
