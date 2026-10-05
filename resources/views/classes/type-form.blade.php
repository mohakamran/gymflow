@php $editing = $class->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit class' : 'New class type'">
    <x-page-header :title="$editing ? 'Edit '.$class->name : 'New class type'">
        <x-slot:actions><x-button variant="secondary" icon="arrow-left" :href="route('classes.types.index')">Back</x-button></x-slot:actions>
    </x-page-header>
    <form method="POST" action="{{ $editing ? route('classes.types.update', $class) : route('classes.types.store') }}" class="max-w-3xl">
        @csrf
        @if ($editing) @method('PUT') @endif
        <x-card>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-input name="name" label="Name" :value="$class->name" placeholder="e.g. Morning Yoga" required autofocus />
                <x-select name="trainer_id" label="Default trainer" :options="$trainers" :value="$class->trainer_id" placeholder="None" />
                <div class="sm:col-span-2"><x-textarea name="description" label="Description" :value="$class->description" rows="3" hint="Shown on your public page and member portal." /></div>
                <x-input name="duration_minutes" type="number" min="5" label="Duration (minutes)" :value="$class->duration_minutes" required />
                <x-input name="capacity" type="number" min="1" label="Capacity" :value="$class->capacity" required />
                <x-input name="location" label="Room / location" :value="$class->location" />
                <div class="space-y-1.5">
                    <label for="color" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Color</label>
                    <input id="color" type="color" name="color" value="{{ old('color', $class->color) }}" class="h-10 w-20 cursor-pointer rounded-lg border border-zinc-300 bg-transparent dark:border-zinc-700">
                </div>
                <x-toggle name="allow_member_booking" label="Members can book online" :checked="$class->allow_member_booking" />
                <x-toggle name="is_active" label="Active" :checked="$class->is_active" />
            </div>
            <x-slot:footer><x-button>{{ $editing ? 'Save' : 'Create class' }}</x-button></x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
