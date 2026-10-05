@php $editing = $announcement->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit announcement' : 'New announcement'">
    <x-page-header :title="$editing ? 'Edit announcement' : 'New announcement'">
        <x-slot:actions><x-button variant="secondary" icon="arrow-left" :href="route('announcements.index')">Back</x-button></x-slot:actions>
    </x-page-header>
    <form method="POST" action="{{ $editing ? route('announcements.update', $announcement) : route('announcements.store') }}" class="max-w-3xl">
        @csrf
        @if ($editing) @method('PUT') @endif
        <x-card>
            <div class="space-y-5">
                <x-input name="title" label="Title" :value="$announcement->title" required autofocus />
                <x-textarea name="body" label="Message" :value="$announcement->body" rows="8" required />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-select name="audience" label="Audience" :options="\App\Enums\AnnouncementAudience::options()" :value="$announcement->audience?->value" required />
                    <div class="flex items-end"><x-toggle name="is_pinned" label="Pin to top" :checked="$announcement->is_pinned" /></div>
                </div>
                @unless ($editing)
                    <label class="flex items-start gap-2 rounded-xl border border-zinc-200 p-4 text-sm dark:border-zinc-800">
                        <input type="checkbox" name="send_email" value="1" class="form-checkbox mt-0.5">
                        <span><span class="font-medium">Also send by email</span><span class="block text-xs text-zinc-500">Sent to everyone in the audience who has an email address (unless announcements are switched off in notification settings).</span></span>
                    </label>
                @endunless
            </div>
            <x-slot:footer><x-button icon="megaphone">{{ $editing ? 'Save' : 'Publish' }}</x-button></x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
