<x-layouts.app title="My attendance">
    <x-page-header title="My attendance" />
    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <x-stat label="Visits this month" :value="$thisMonth" icon="qr" />
        <x-stat label="Total visits" :value="$total" icon="chart" />
    </div>
    <x-card :padding="false">
        @if ($visits->isEmpty())
            <x-empty-state icon="qr" title="No visits yet" />
        @else
            <table class="table-default">
                <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Duration</th></tr></thead>
                <tbody>
                    @foreach ($visits as $visit)
                        <tr><td>{{ format_date($visit->checked_in_at->copy()->setTimezone(tenant_timezone())) }}</td><td>{{ format_time($visit->checked_in_at) }}</td><td>{{ $visit->checked_out_at ? format_time($visit->checked_out_at) : '—' }}</td><td>{{ $visit->durationMinutes() ? $visit->durationMinutes().' min' : '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $visits->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
