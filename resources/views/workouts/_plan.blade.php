{{-- Shared rendering of a workout plan (staff view and member portal). --}}
<div class="space-y-6">
    <div class="flex flex-wrap gap-6 text-sm">
        @if ($plan->goal)<div><p class="text-xs text-zinc-500">Goal</p><p class="font-medium">{{ $plan->goal }}</p></div>@endif
        @if ($plan->starts_on)<div><p class="text-xs text-zinc-500">Dates</p><p class="font-medium">{{ format_date($plan->starts_on) }}@if ($plan->ends_on) – {{ format_date($plan->ends_on) }}@endif</p></div>@endif
        <div><p class="text-xs text-zinc-500">Coach</p><p class="font-medium">{{ $plan->trainer?->name ?? 'Staff' }}</p></div>
    </div>
    @if ($plan->notes)<p class="rounded-xl bg-zinc-50 p-4 text-sm whitespace-pre-line dark:bg-zinc-800/50">{{ $plan->notes }}</p>@endif
    @forelse ($plan->exercisesByDay() as $day => $exercises)
        <div class="card overflow-hidden">
            <h3 class="border-b border-zinc-100 bg-zinc-50/60 px-5 py-3 text-sm font-semibold dark:border-zinc-800 dark:bg-zinc-900/60">{{ $day }}</h3>
            <div class="overflow-x-auto">
                <table class="table-default">
                    <thead><tr><th>Exercise</th><th>Sets</th><th>Reps</th><th>Rest</th><th>Notes</th></tr></thead>
                    <tbody>
                        @foreach ($exercises as $exercise)
                            <tr><td class="font-medium">{{ $exercise['name'] }}</td><td>{{ $exercise['sets'] ?? '—' }}</td><td>{{ $exercise['reps'] ?? '—' }}</td><td>{{ $exercise['rest'] ?? '—' }}</td><td class="text-zinc-500">{{ $exercise['notes'] ?? '' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <x-empty-state icon="bolt" title="No exercises in this plan yet" />
    @endforelse
</div>
