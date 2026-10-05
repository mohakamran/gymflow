<x-layouts.app title="My membership">
    <x-page-header title="My membership" />
    @php $current = $member->currentMembership; @endphp
    @if ($current)
        <x-card class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div><p class="text-xl font-semibold">{{ $current->plan->name }}</p><p class="text-sm text-zinc-500">{{ format_date($current->starts_on) }} – {{ format_date($current->ends_on) }} · {{ $current->daysRemaining() }} days left</p></div>
                <x-badge :color="$current->displayStatus()['color']">{{ $current->displayStatus()['label'] }}</x-badge>
            </div>
            @if ($current->plan->features)
                <ul class="mt-4 grid gap-1.5 text-sm sm:grid-cols-2">@foreach ($current->plan->features as $feature)<li class="flex gap-2"><x-icon name="check" class="size-4 text-brand-600" />{{ $feature }}</li>@endforeach</ul>
            @endif
            @if ($current->plan->class_limit_per_week)<p class="mt-3 text-sm text-zinc-500">Includes {{ $current->plan->class_limit_per_week }} classes per week.</p>@endif
        </x-card>
    @endif
    <x-card title="History" :padding="false">
        @forelse ($memberships as $membership)
            <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-3 last:border-0 dark:border-zinc-800">
                <div><p class="text-sm font-medium">{{ $membership->plan->name }}</p><p class="text-xs text-zinc-500">{{ format_date($membership->starts_on) }} – {{ format_date($membership->ends_on) }}</p></div>
                <x-badge :color="$membership->displayStatus()['color']">{{ $membership->displayStatus()['label'] }}</x-badge>
            </div>
        @empty
            <x-empty-state icon="card" title="No memberships yet" />
        @endforelse
    </x-card>
</x-layouts.app>
