<x-layouts.app title="My progress">
    <x-page-header title="My progress" description="Measurements recorded by your trainer." />
    @php $weights = $records->whereNotNull('weight_kg'); @endphp
    @if ($weights->count() > 1)
        <x-card title="Weight" class="mb-6">
            <x-chart title="Weight over time" :config="['type' => 'line', 'labels' => $weights->map(fn ($r) => $r->recorded_on->format('M j'))->values()->all(), 'format' => 'number', 'labelHeading' => 'Date', 'series' => [['label' => 'Weight (kg)', 'data' => $weights->map(fn ($r) => (float) $r->weight_kg)->values()->all()]]]" />
        </x-card>
    @endif
    <x-card :padding="false">
        @if ($records->isEmpty())
            <x-empty-state icon="chart" title="No measurements yet" />
        @else
            <div class="overflow-x-auto">
                <table class="table-default">
                    <thead><tr><th>Date</th>@foreach ($measurements as $label)<th class="text-right">{{ $label }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($records->sortByDesc('recorded_on') as $record)
                            <tr><td>{{ format_date($record->recorded_on) }}</td>@foreach (array_keys($measurements) as $f)<td class="text-right tabular-nums">{{ $record->$f !== null ? rtrim(rtrim($record->$f, '0'), '.') : '—' }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layouts.app>
