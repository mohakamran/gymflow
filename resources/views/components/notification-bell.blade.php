@php
    $notifiable = auth()->user()?->member && auth()->user()->hasRole('member') ? auth()->user()->member : auth()->user();
    $unread = $notifiable?->unreadNotifications()->count() ?? 0;
    $href = $notifiable instanceof \App\Models\Member ? route('portal.notifications') : route('notifications.index');
@endphp
<a href="{{ $href }}" class="relative rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white" aria-label="Notifications{{ $unread ? " ($unread unread)" : '' }}">
    <x-icon name="bell" class="size-5" />
    @if ($unread)
        <span class="absolute top-1 right-1 grid min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[10px] leading-4 font-bold text-white">{{ $unread > 9 ? '9+' : $unread }}</span>
    @endif
</a>
