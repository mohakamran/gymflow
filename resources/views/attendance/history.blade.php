<x-layouts.app title="Attendance history">
    <x-page-header title="Attendance history" :description="number_format($visits->total()).' visits · '.number_format($uniqueMembers).' unique members'">
        <x-slot:actions>
            <x-button variant="secondary" icon="arrow-left" :href="route('attendance.index')">Front desk</x-button>
            @can(\App\Enums\Permission::ReportsView)
                <x-button variant="secondary" icon="chart" :href="route('reports.show', ['attendance', 'from' => $filters['from'], 'to' => $filters['to']])">Report</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-card title="Visits per day" class="mb-6">
        <x-chart title="Visits per day" :config="['type' => 'bar', 'labels' => $daily->keys()->map(fn ($d) => \Carbon\Carbon::parse($d)->format('M j'))->values()->all(), 'format' => 'number', 'labelHeading' => 'Day', 'series' => [['label' => 'Visits', 'data' => $daily->values()->all()]]]" height="h-56" />
    </x-card>

    <x-card :padding="false">
        <x-filters :reset="route('attendance.history')">
            <x-input name="from" type="date" label="From" :value="$filters['from']" />
            <x-input name="to" type="date" label="To" :value="$filters['to']" />
            <x-select name="method" label="Method" :options="\App\Enums\AttendanceMethod::options()" :value="$filters['method'] ?? null" placeholder="Any" />
            <x-input name="search" type="search" label="Member" :value="$filters['search'] ?? null" placeholder="Name or code" />
        </x-filters>
        @if ($visits->isEmpty())
            <x-empty-state icon="qr" title="No visits in this range" />
        @else
            <div class="overflow-x-auto">
                <table class="table-default">
                    <thead><tr><th>Date</th><th>Member</th><th>In</th><th>Out</th><th>Duration</th><th class="hidden md:table-cell">Method</th><th class="hidden md:table-cell">Recorded by</th></tr></thead>
                    <tbody>
                        @foreach ($visits as $visit)
                            <tr>
                                <td class="whitespace-nowrap">{{ format_date($visit->checked_in_at->copy()->setTimezone(tenant_timezone())) }}</td>
                                <td><a href="{{ route('members.show', $visit->member) }}" class="font-medium hover:text-brand-600">{{ $visit->member->full_name }}</a></td>
                                <td class="tabular-nums">{{ format_time($visit->checked_in_at) }}</td>
                                <td class="tabular-nums">{{ $visit->checked_out_at ? format_time($visit->checked_out_at) : '—' }}</td>
                                <td>{{ $visit->durationMinutes() !== null ? $visit->durationMinutes().' min' : '—' }}</td>
                                <td class="hidden md:table-cell">{{ $visit->method->label() }}</td>
                                <td class="hidden md:table-cell">{{ $visit->recorder?->name ?? 'Self' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $visits->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
