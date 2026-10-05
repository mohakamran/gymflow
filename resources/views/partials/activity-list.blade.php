@php /** @var \Illuminate\Support\Collection<int, \App\Models\AuditLog> $activity */ @endphp
@if ($activity->isEmpty())
    <x-empty-state icon="clipboard" title="No activity yet" description="Changes made in your workspace will appear here." />
@else
    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
        @foreach ($activity as $log)
            <li class="flex items-start gap-3 px-5 py-3.5">
                <x-avatar :user="$log->user" size="size-8" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">
                        <span class="font-medium text-zinc-900 dark:text-white">{{ $log->user?->name ?? 'System' }}</span>
                        <span class="text-zinc-500 dark:text-zinc-400">·</span>
                        <x-badge :color="$log->eventColor()">{{ $log->event }}</x-badge>
                        @if ($log->subjectLabel())<span class="text-zinc-500 dark:text-zinc-400">on {{ $log->subjectLabel() }} #{{ $log->auditable_id }}</span>@endif
                    </p>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400" title="{{ $log->created_at->toDayDateTimeString() }}">{{ $log->created_at->diffForHumans() }}</p>
                </div>
            </li>
        @endforeach
    </ul>
@endif
