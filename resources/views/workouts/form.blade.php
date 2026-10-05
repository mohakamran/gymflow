@php
    $editing = $plan->exists;
    $exercises = old('exercises', $plan->exercises ?: [['day' => 'Day 1', 'name' => '', 'sets' => 3, 'reps' => '10', 'rest' => '60s', 'notes' => '']]);
@endphp
<x-layouts.app :title="$editing ? 'Edit workout plan' : 'New workout plan'">
    <x-page-header :title="$editing ? 'Edit '.$plan->title : 'New workout plan'" :description="'For '.$member->full_name">
        <x-slot:actions><x-button variant="secondary" icon="arrow-left" :href="route('members.show', $member).'#workouts'">Back</x-button></x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('members.workouts.update', [$member, $plan]) : route('members.workouts.store', $member) }}" class="space-y-6"
          x-data="{ rows: @js(array_values($exercises)), add(day) { this.rows.push({ day: day ?? (this.rows.at(-1)?.day ?? 'Day 1'), name: '', sets: 3, reps: '10', rest: '60s', notes: '' }) } }">
        @csrf
        @if ($editing) @method('PUT') @endif
        <x-card title="Plan">
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2"><x-input name="title" label="Title" :value="$plan->title" placeholder="e.g. 8-week strength block" required /></div>
                <div class="sm:col-span-2"><x-input name="goal" label="Goal" :value="$plan->goal" placeholder="e.g. Build strength, lose 4 kg" /></div>
                <x-input name="starts_on" type="date" label="Start" :value="$plan->starts_on?->toDateString()" />
                <x-input name="ends_on" type="date" label="End" :value="$plan->ends_on?->toDateString()" />
                <div class="flex items-end sm:col-span-2"><x-toggle name="is_active" label="Active plan" :checked="$plan->is_active" description="Shown to the member in their portal." /></div>
                <div class="sm:col-span-2 lg:col-span-4"><x-textarea name="notes" label="Coaching notes" :value="$plan->notes" rows="2" /></div>
            </div>
        </x-card>

        <x-card title="Exercises" :padding="false">
            <div class="overflow-x-auto">
                <table class="table-default">
                    <thead><tr><th class="w-32">Day</th><th>Exercise</th><th class="w-20">Sets</th><th class="w-24">Reps</th><th class="w-24">Rest</th><th>Notes</th><th class="w-10"></th></tr></thead>
                    <tbody>
                        <template x-for="(row, index) in rows" :key="index">
                            <tr>
                                <td><input class="form-control" :name="`exercises[${index}][day]`" x-model="row.day" aria-label="Day"></td>
                                <td><input class="form-control min-w-40" :name="`exercises[${index}][name]`" x-model="row.name" placeholder="Back squat" aria-label="Exercise"></td>
                                <td><input class="form-control" type="number" min="1" :name="`exercises[${index}][sets]`" x-model="row.sets" aria-label="Sets"></td>
                                <td><input class="form-control" :name="`exercises[${index}][reps]`" x-model="row.reps" aria-label="Reps"></td>
                                <td><input class="form-control" :name="`exercises[${index}][rest]`" x-model="row.rest" aria-label="Rest"></td>
                                <td><input class="form-control min-w-32" :name="`exercises[${index}][notes]`" x-model="row.notes" aria-label="Notes"></td>
                                <td><button type="button" @click="rows.splice(index, 1)" class="rounded p-1 text-zinc-400 hover:text-rose-600" aria-label="Remove"><x-icon name="trash" class="size-4" /></button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            @error('exercises.*.name')<p class="px-4 pt-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            <div class="flex flex-wrap gap-2 border-t border-zinc-100 p-4 dark:border-zinc-800">
                <x-button type="button" size="sm" variant="secondary" icon="plus" @click="add()">Add exercise</x-button>
                <x-button type="button" size="sm" variant="ghost" icon="plus" @click="add('Day ' + (new Set(rows.map(r => r.day)).size + 1))">Add day</x-button>
            </div>
            <x-slot:footer><x-button>{{ $editing ? 'Save plan' : 'Create plan' }}</x-button></x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
