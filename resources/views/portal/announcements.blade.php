<x-layouts.app title="Announcements">
    <x-page-header title="Announcements" />
    <div class="space-y-4">
        @forelse ($announcements as $announcement)
            <article class="card p-5">
                <h3 class="font-semibold">{{ $announcement->title }}</h3>
                <p class="text-xs text-zinc-500">{{ $announcement->published_at?->toFormattedDateString() }}</p>
                <p class="mt-3 text-sm whitespace-pre-line">{{ $announcement->body }}</p>
            </article>
        @empty
            <div class="card"><x-empty-state icon="megaphone" title="No announcements" /></div>
        @endforelse
        {{ $announcements->links() }}
    </div>
</x-layouts.app>
