<x-layouts.app title="Platform overview">
    <x-page-header title="Platform overview" description="Every gym on {{ config('app.name') }} at a glance." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Gyms" :value="number_format($stats['gyms'])" icon="building" :hint="$stats['signupsThisMonth'].' new this month'" />
        <x-stat label="Active" :value="number_format($stats['active'])" icon="check-circle" hint="Paying gyms" />
        <x-stat label="On trial" :value="number_format($stats['trial'])" icon="sparkles" :hint="$stats['suspended'].' suspended'" />
        <x-stat label="Gym users" :value="number_format($stats['users'])" icon="users" hint="Across all tenants" />
    </div>

    @if ($pendingRequests > 0)
        <x-alert type="info" class="mt-6">{{ $pendingRequests }} plan change {{ \Illuminate\Support\Str::plural('request', $pendingRequests) }} waiting for review. <a href="{{ route('admin.plans.index') }}" class="font-semibold underline">Review</a></x-alert>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        <x-card title="Newest gyms" :padding="false" class="lg:col-span-3">
            <x-slot:actions><x-button variant="ghost" size="sm" :href="route('admin.tenants.index')">All gyms</x-button></x-slot:actions>
            <div class="overflow-x-auto">
                <table class="table-default">
                    <thead><tr><th>Gym</th><th>Plan</th><th>Status</th><th class="text-right">Users</th></tr></thead>
                    <tbody>
                        @forelse ($latestGyms as $gym)
                            <tr>
                                <td><a href="{{ route('admin.tenants.show', $gym) }}" class="font-medium text-zinc-900 hover:text-brand-600 dark:text-white">{{ $gym->name }}</a><p class="text-xs text-zinc-500">{{ $gym->created_at->toFormattedDateString() }}</p></td>
                                <td>{{ $gym->subscription_plan->label() }}</td>
                                <td><x-badge :color="$gym->status->color()">{{ $gym->status->label() }}</x-badge></td>
                                <td class="text-right tabular-nums">{{ $gym->users_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty-state icon="building" title="No gyms yet" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
        <x-card title="Platform activity" :padding="false" class="lg:col-span-2">
            @include('partials.activity-list', ['activity' => $recentActivity])
        </x-card>
    </div>
</x-layouts.app>
