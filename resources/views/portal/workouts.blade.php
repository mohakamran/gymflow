<x-layouts.app title="My workouts">
    <x-page-header title="My workouts" />
    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($plans as $plan)
            <a href="{{ route('portal.workouts.show', $plan) }}" class="card p-5 hover:border-brand-200">
                <div class="flex items-start justify-between gap-2"><h3 class="font-semibold">{{ $plan->title }}</h3>@if ($plan->is_active)<x-badge color="emerald">Active</x-badge>@else<x-badge>Past</x-badge>@endif</div>
                <p class="mt-1 text-sm text-zinc-500">{{ $plan->goal }}</p>
                <p class="mt-3 text-xs text-zinc-500">{{ count($plan->exercises ?? []) }} exercises · {{ $plan->trainer?->name ?? 'Staff' }}</p>
            </a>
        @empty
            <div class="card md:col-span-2"><x-empty-state icon="bolt" title="No workout plans yet" description="Ask your trainer to build one for you." /></div>
        @endforelse
    </div>
</x-layouts.app>
