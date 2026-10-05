<x-layouts.app title="Audit log">
    <x-page-header title="Audit log" description="A tamper-evident trail of sign-ins and changes in your workspace." />

    <x-card :padding="false">
        <form method="GET" class="grid gap-3 border-b border-zinc-100 p-4 sm:grid-cols-2 lg:grid-cols-5 dark:border-zinc-800">
            <select name="event" class="form-control" aria-label="Event type">
                <option value="">All events</option>
                @foreach ($eventGroups as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['event'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="user" class="form-control" aria-label="User">
                <option value="">All users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((string) ($filters['user'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control" aria-label="From date">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control" aria-label="To date">
            <div class="flex gap-2">
                <x-button class="flex-1">Filter</x-button>
                @if (array_filter($filters))<x-button variant="ghost" :href="route('audit-logs.index')">Reset</x-button>@endif
            </div>
        </form>

        @if ($logs->isEmpty())
            <x-empty-state icon="clipboard" title="No matching entries" description="Try widening the date range or clearing filters." />
        @else
            <div class="overflow-x-auto">
                <table class="table-default">
                    <thead><tr><th>When</th><th>User</th><th>Event</th><th>Subject</th><th class="hidden md:table-cell">IP address</th><th><span class="sr-only">Details</span></th></tr></thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr x-data="{ open: false }">
                                <td class="whitespace-nowrap"><span title="{{ $log->created_at->toDayDateTimeString() }}">{{ $log->created_at->diffForHumans() }}</span></td>
                                <td><div class="flex items-center gap-2"><x-avatar :user="$log->user" size="size-7" /><span class="whitespace-nowrap">{{ $log->user?->name ?? 'System' }}</span></div></td>
                                <td><x-badge :color="$log->eventColor()">{{ $log->event }}</x-badge></td>
                                <td class="whitespace-nowrap">{{ $log->subjectLabel() ? $log->subjectLabel().' #'.$log->auditable_id : '—' }}</td>
                                <td class="hidden font-mono text-xs md:table-cell">{{ $log->ip_address }}</td>
                                <td class="text-right">
                                    @if ($log->old_values || $log->new_values)
                                        <button type="button" @click="open = !open" class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">Changes</button>
                                        <template x-teleport="body">
                                            <div x-show="open" x-cloak class="fixed inset-0 z-[55] grid place-items-center bg-zinc-950/50 p-4 backdrop-blur-sm" @click.self="open = false" @keydown.escape.window="open = false">
                                                <div class="card max-h-[80vh] w-full max-w-2xl overflow-y-auto p-5">
                                                    <div class="mb-4 flex items-center justify-between"><h3 class="font-semibold">{{ $log->event }}</h3><button @click="open = false" class="text-zinc-400" aria-label="Close"><x-icon name="x" /></button></div>
                                                    <table class="table-default">
                                                        <thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead>
                                                        <tbody>
                                                            @foreach (array_unique(array_merge(array_keys($log->old_values ?? []), array_keys($log->new_values ?? []))) as $field)
                                                                <tr>
                                                                    <td class="font-medium">{{ $field }}</td>
                                                                    <td class="font-mono text-xs break-all text-rose-600 dark:text-rose-400">{{ is_scalar($v = data_get($log->old_values, $field)) || $v === null ? ($v ?? '—') : json_encode($v) }}</td>
                                                                    <td class="font-mono text-xs break-all text-emerald-600 dark:text-emerald-400">{{ is_scalar($v = data_get($log->new_values, $field)) || $v === null ? ($v ?? '—') : json_encode($v) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </template>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $logs->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
