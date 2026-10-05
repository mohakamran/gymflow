@forelse ($notifications as $notification)
    <div @class(['flex gap-3 border-b border-zinc-100 px-5 py-4 last:border-0 dark:border-zinc-800', 'bg-brand-50/40 dark:bg-brand-500/5' => ! $notification->read_at])>
        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800"><x-icon :name="$notification->data['icon'] ?? 'bell'" class="size-4" /></span>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium">{{ $notification->data['title'] ?? 'Notification' }}</p>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $notification->data['body'] ?? '' }}</p>
            <p class="mt-1 text-xs text-zinc-400">{{ $notification->created_at->diffForHumans() }}</p>
        </div>
        @if (isset($readRoute) && ! $notification->read_at)
            <a href="{{ route($readRoute, $notification->id) }}" class="text-xs font-semibold text-brand-600">Open</a>
        @endif
    </div>
@empty
    <x-empty-state icon="bell" title="You're all caught up" />
@endforelse
