<x-layouts.app title="Membership plans">
    <x-page-header title="Membership plans" description="What you sell. Price changes only apply to new sales.">
        <x-slot:actions><x-button icon="plus" :href="route('plans.create')">New plan</x-button></x-slot:actions>
    </x-page-header>

    @if ($plans->isEmpty())
        <div class="card"><x-empty-state icon="sparkles" title="Create your first plan" description="Monthly, quarterly, yearly or a custom duration — with pricing, joining fee and class limits."><x-button icon="plus" :href="route('plans.create')">New plan</x-button></x-empty-state></div>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($plans as $plan)
                <div @class(['card flex flex-col overflow-hidden', 'opacity-60' => ! $plan->is_active])>
                    <div class="h-1.5" style="background: {{ $plan->color }}"></div>
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold">{{ $plan->name }}</h3>
                                <p class="text-xs text-zinc-500">{{ $plan->durationLabel() }}</p>
                            </div>
                            <div class="flex flex-wrap justify-end gap-1">
                                @unless ($plan->is_active)<x-badge>Retired</x-badge>@endunless
                                @unless ($plan->is_public)<x-badge>Hidden</x-badge>@endunless
                            </div>
                        </div>
                        <p class="mt-4">
                            <span class="text-3xl font-semibold tracking-tight">{{ money($plan->effectivePrice()) }}</span>
                            @if ((float) $plan->discount_percent > 0)<span class="ml-1 text-sm text-zinc-400 line-through">{{ money($plan->price) }}</span>@endif
                        </p>
                        @if ((float) $plan->signup_fee > 0)<p class="text-xs text-zinc-500">+ {{ money($plan->signup_fee) }} joining fee</p>@endif
                        <ul class="mt-4 flex-1 space-y-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                            @foreach ($plan->features ?? [] as $feature)<li class="flex gap-2"><x-icon name="check" class="size-4 shrink-0 text-brand-600" />{{ $feature }}</li>@endforeach
                            @if ($plan->class_limit_per_week)<li class="flex gap-2"><x-icon name="calendar" class="size-4 shrink-0 text-zinc-400" />{{ $plan->class_limit_per_week }} classes / week</li>@endif
                            @if ($plan->access_hours)<li class="flex gap-2"><x-icon name="clock" class="size-4 shrink-0 text-zinc-400" />{{ $plan->access_hours }}</li>@endif
                        </ul>
                        <div class="mt-5 flex items-center justify-between border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            <span class="text-sm text-zinc-500"><span class="font-semibold text-zinc-900 dark:text-white">{{ $plan->active_count }}</span> active</span>
                            <div class="flex gap-1">
                                <x-button size="sm" variant="ghost" :href="route('plans.edit', $plan)">Edit</x-button>
                                <x-confirm :action="route('plans.destroy', $plan)" method="DELETE" title="Delete {{ $plan->name }}?" message="Plans with active members are retired instead of deleted." trigger="Delete" confirm="Delete" />
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app>
