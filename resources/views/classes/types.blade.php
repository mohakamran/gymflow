<x-layouts.app title="Class types">
    <x-page-header title="Class types" description="The classes you offer. Schedule them on the timetable.">
        <x-slot:actions>
            <x-button variant="secondary" icon="calendar" :href="route('classes.index')">Schedule</x-button>
            @can('create', \App\Models\GymClass::class)<x-button icon="plus" :href="route('classes.types.create')">New class type</x-button>@endcan
        </x-slot:actions>
    </x-page-header>
    @if ($classes->isEmpty())
        <div class="card"><x-empty-state icon="calendar" title="No classes yet" description="Add Yoga, Spin, HIIT, Personal Training…"><x-button icon="plus" :href="route('classes.types.create')">New class type</x-button></x-empty-state></div>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($classes as $class)
                <div @class(['card overflow-hidden', 'opacity-60' => ! $class->is_active])>
                    <div class="h-1.5" style="background: {{ $class->color }}"></div>
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-semibold">{{ $class->name }}</h3>
                            @unless ($class->is_active)<x-badge>Inactive</x-badge>@endunless
                        </div>
                        @if ($class->description)<p class="mt-1 line-clamp-2 text-sm text-zinc-500">{{ $class->description }}</p>@endif
                        <dl class="mt-4 grid grid-cols-3 gap-2 text-xs">
                            <div><dt class="text-zinc-500">Duration</dt><dd class="font-semibold">{{ $class->duration_minutes }} min</dd></div>
                            <div><dt class="text-zinc-500">Capacity</dt><dd class="font-semibold">{{ $class->capacity }}</dd></div>
                            <div><dt class="text-zinc-500">Upcoming</dt><dd class="font-semibold">{{ $class->upcoming_count }}</dd></div>
                        </dl>
                        <p class="mt-3 text-xs text-zinc-500">{{ $class->trainer?->name ?? 'No default trainer' }}@if ($class->location) · {{ $class->location }}@endif</p>
                        <div class="mt-4 flex justify-end gap-1">
                            <x-button size="sm" variant="ghost" :href="route('classes.sessions.create', ['class' => $class->id])">Schedule</x-button>
                            <x-button size="sm" variant="ghost" :href="route('classes.types.edit', $class)">Edit</x-button>
                            <x-confirm :action="route('classes.types.destroy', $class)" method="DELETE" title="Delete {{ $class->name }}?" message="Upcoming sessions are cancelled and their bookings released." trigger="Delete" confirm="Delete" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app>
