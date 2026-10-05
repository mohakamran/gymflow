<x-layouts.app title="Schedule class">
    <x-page-header title="Schedule a class" description="Add a single session or a weekly recurring series.">
        <x-slot:actions><x-button variant="secondary" icon="arrow-left" :href="route('classes.index')">Back</x-button></x-slot:actions>
    </x-page-header>

    @if ($classes->isEmpty())
        <div class="card"><x-empty-state icon="calendar" title="Create a class type first" description="e.g. Yoga, HIIT, Spin — with default trainer, capacity and duration."><x-button :href="route('classes.types.create')">New class type</x-button></x-empty-state></div>
    @else
        <form method="POST" action="{{ route('classes.sessions.store') }}" class="grid max-w-4xl gap-6 lg:grid-cols-2" x-data="{ repeat: @js((bool) old('repeat_days')) }">
            @csrf
            <x-card title="Session">
                <div class="space-y-5">
                    <x-select name="gym_class_id" label="Class" :options="$classes->pluck('name', 'id')" :value="$selectedClass" required />
                    <div class="grid grid-cols-2 gap-4">
                        <x-input name="date" type="date" label="Date" :value="tenant_today()->toDateString()" required />
                        <x-input name="start_time" type="time" label="Start time" value="18:00" required />
                    </div>
                    <x-select name="trainer_id" label="Trainer" :options="$trainers" placeholder="Class default" />
                    <div class="grid grid-cols-2 gap-4">
                        <x-input name="capacity" type="number" min="1" label="Capacity" hint="Blank = class default" />
                        <x-input name="location" label="Room / location" hint="Blank = class default" />
                    </div>
                </div>
            </x-card>
            <x-card title="Repeat">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="form-checkbox" x-model="repeat"> Repeat weekly</label>
                <div x-show="repeat" x-cloak class="mt-5 space-y-5">
                    <div>
                        <p class="mb-2 text-sm font-medium">On these days</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'] as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="repeat_days[]" value="{{ $value }}" class="peer sr-only" :disabled="!repeat" @checked(in_array($value, old('repeat_days', [])))>
                                    <span class="block rounded-lg px-3 py-1.5 text-sm font-medium ring-1 ring-zinc-300 peer-checked:bg-brand-600 peer-checked:text-white peer-checked:ring-brand-600 dark:ring-zinc-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('repeat_days')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <x-input name="repeat_until" type="date" label="Until" :value="tenant_today()->addWeeks(8)->toDateString()" x-bind:disabled="!repeat" />
                </div>
                <x-slot:footer><x-button icon="calendar">Schedule</x-button></x-slot:footer>
            </x-card>
        </form>
    @endif
</x-layouts.app>
