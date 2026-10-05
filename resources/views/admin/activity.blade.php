<x-layouts.app title="Platform activity">
    <x-page-header title="Platform activity" description="Audit trail across all gyms." />
    <x-card :padding="false">
        <x-filters :reset="route('admin.activity.index')">
            <x-select name="tenant" label="Gym" :options="$tenants" :value="$filters['tenant'] ?? null" placeholder="All gyms" />
            <x-input name="event" label="Event starts with" :value="$filters['event'] ?? null" placeholder="e.g. auth, payment" />
        </x-filters>
        <div class="overflow-x-auto">
            <table class="table-default">
                <thead><tr><th>When</th><th>Gym</th><th>User</th><th>Event</th><th>Subject</th><th class="hidden md:table-cell">IP</th></tr></thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap" title="{{ $log->created_at }}">{{ $log->created_at->diffForHumans() }}</td>
                            <td>{{ $log->tenant?->name ?? 'Platform' }}</td>
                            <td>{{ $log->user?->name ?? 'System' }}</td>
                            <td><x-badge :color="$log->eventColor()">{{ $log->event }}</x-badge></td>
                            <td>{{ $log->subjectLabel() ? $log->subjectLabel().' #'.$log->auditable_id : '—' }}</td>
                            <td class="hidden font-mono text-xs md:table-cell">{{ $log->ip_address }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $logs->links() }}</div>
    </x-card>
</x-layouts.app>
