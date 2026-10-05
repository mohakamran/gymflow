<x-layouts.app title="Gyms">
    <x-page-header title="Gyms" description="All tenants on the platform." />

    <x-card :padding="false">
        <form method="GET" class="grid gap-3 border-b border-zinc-100 p-4 sm:grid-cols-[1fr_auto_auto_auto] dark:border-zinc-800">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-zinc-400" />
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, email or slug…" class="form-control pl-9">
            </div>
            <select name="status" class="form-control" aria-label="Status">
                <option value="">Any status</option>
                @foreach (\App\Enums\TenantStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>@endforeach
            </select>
            <select name="plan" class="form-control" aria-label="Plan">
                <option value="">Any plan</option>
                @foreach (\App\Enums\SubscriptionPlan::cases() as $plan)<option value="{{ $plan->value }}" @selected(($filters['plan'] ?? '') === $plan->value)>{{ $plan->label() }}</option>@endforeach
            </select>
            <x-button>Filter</x-button>
        </form>

        @if ($tenants->isEmpty())
            <x-empty-state icon="building" title="No gyms found" description="Try a different search or filter." />
        @else
            <div class="overflow-x-auto">
                <table class="table-default">
                    <thead><tr><th>Gym</th><th class="hidden md:table-cell">Contact</th><th>Plan</th><th>Status</th><th class="text-right">Users</th><th class="hidden lg:table-cell">Created</th></tr></thead>
                    <tbody>
                        @foreach ($tenants as $gym)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.tenants.show', $gym) }}" class="flex items-center gap-3">
                                        <span class="grid size-8 shrink-0 place-items-center rounded-lg text-xs font-bold text-white" style="background: {{ $gym->primary_color }}">{{ $gym->initials }}</span>
                                        <span class="font-medium text-zinc-900 hover:text-brand-600 dark:text-white">{{ $gym->name }}</span>
                                    </a>
                                </td>
                                <td class="hidden md:table-cell">{{ $gym->email }}</td>
                                <td>{{ $gym->subscription_plan->label() }}</td>
                                <td><x-badge :color="$gym->status->color()">{{ $gym->status->label() }}</x-badge></td>
                                <td class="text-right tabular-nums">{{ $gym->users_count }}</td>
                                <td class="hidden whitespace-nowrap lg:table-cell">{{ $gym->created_at->toFormattedDateString() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $tenants->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
