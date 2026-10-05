<x-layouts.app title="Announcements">
    <x-page-header title="Announcements" description="News for your members and team — shown in the portal and optionally emailed.">
        <x-slot:actions><x-button icon="plus" :href="route('announcements.create')">New announcement</x-button></x-slot:actions>
    </x-page-header>
    <div class="space-y-4">
        @forelse ($announcements as $announcement)
            <article class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold">@if ($announcement->is_pinned)<span class="mr-1 text-brand-600">📌</span>@endif{{ $announcement->title }}</h3>
                        <p class="text-xs text-zinc-500">{{ $announcement->published_at?->diffForHumans() }} by {{ $announcement->author?->name ?? 'Staff' }} · {{ $announcement->audience->label() }}@if ($announcement->emailed_at) · emailed @endif</p>
                    </div>
                    <div class="flex gap-1">
                        <x-button size="sm" variant="ghost" :href="route('announcements.edit', $announcement)">Edit</x-button>
                        <x-confirm :action="route('announcements.destroy', $announcement)" method="DELETE" title="Delete this announcement?" trigger="Delete" confirm="Delete" />
                    </div>
                </div>
                <p class="mt-3 text-sm whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $announcement->body }}</p>
            </article>
        @empty
            <div class="card"><x-empty-state icon="megaphone" title="No announcements yet" description="Holiday hours, new classes, events…"><x-button icon="plus" :href="route('announcements.create')">New announcement</x-button></x-empty-state></div>
        @endforelse
        {{ $announcements->links() }}
    </div>
</x-layouts.app>
