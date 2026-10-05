<x-layouts.app title="Memberships">
    <x-page-header title="Memberships" description="Track who is active, expiring, frozen or lapsed.">
        @can(\App\Enums\Permission::MembershipsManage)
            <x-slot:actions><x-button icon="plus" :href="route('memberships.create')">Sell membership</x-button></x-slot:actions>
        @endcan
    </x-page-header>

    <div class="-mx-4 mb-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
        <div class="flex min-w-max gap-2">
            @foreach (['active' => 'Active', 'expiring' => 'Expiring', 'pending' => 'Upcoming', 'suspended' => 'Frozen', 'expired' => 'Expired', 'cancelled' => 'Cancelled'] as $key => $label)
                <a href="{{ route('memberships.index', array_merge(request()->except('page'), ['state' => $key])) }}"
                   @class(['inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-medium transition', 'bg-brand-600 text-white' => $state === $key, 'bg-white text-zinc-600 ring-1 ring-zinc-200 hover:bg-zinc-50 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-800' => $state !== $key])>
                    {{ $label }} <span @class(['rounded-full px-1.5 text-xs tabular-nums', 'bg-white/20' => $state === $key, 'bg-zinc-100 dark:bg-zinc-800' => $state !== $key])>{{ $counts[$key] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <x-card :padding="false">
        <x-filters :reset="route('memberships.index', ['state' => $state])">
            <input type="hidden" name="state" value="{{ $state }}">
            <div class="relative min-w-56 flex-1">
                <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-zinc-400" />
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search member" class="form-control pl-9" aria-label="Search">
            </div>
            <select name="plan" class="form-control w-auto" aria-label="Plan">
                <option value="">All plans</option>
                @foreach ($plans as $id => $name)<option value="{{ $id }}" @selected((string) ($filters['plan'] ?? '') === (string) $id)>{{ $name }}</option>@endforeach
            </select>
        </x-filters>

        @if ($memberships->isEmpty())
            <x-empty-state icon="card" title="Nothing here" description="No memberships in this state." />
        @else
            <div class="overflow-x-auto">
                <table class="table-default">
                    <thead><tr><th>Member</th><th>Plan</th><th>Period</th><th class="hidden md:table-cell">Remaining</th><th class="hidden md:table-cell">Invoice</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($memberships as $membership)
                            @php $d = $membership->displayStatus(); @endphp
                            <tr>
                                <td><a href="{{ route('members.show', $membership->member) }}" class="font-medium text-zinc-900 hover:text-brand-600 dark:text-white">{{ $membership->member->full_name }}</a><p class="text-xs text-zinc-500">{{ $membership->member->member_code }}</p></td>
                                <td><span class="mr-1.5 inline-block size-2 rounded-full" style="background: {{ $membership->plan->color }}"></span>{{ $membership->plan->name }}</td>
                                <td class="whitespace-nowrap">{{ format_date($membership->starts_on) }} – {{ format_date($membership->ends_on) }}</td>
                                <td class="hidden md:table-cell">
                                    @if (in_array($membership->status, [\App\Enums\MembershipStatus::Active], true))
                                        <div class="flex items-center gap-2"><div class="h-1.5 w-20 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"><div class="h-full rounded-full bg-brand-600" style="width: {{ $membership->progressPercent() }}%"></div></div><span class="text-xs tabular-nums">{{ $membership->daysRemaining() }}d</span></div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="hidden md:table-cell">@if ($membership->invoice)<a href="{{ route('invoices.show', $membership->invoice) }}" class="text-xs"><x-status-badge :status="$membership->invoice->status" /></a>@else — @endif</td>
                                <td><x-badge :color="$d['color']">{{ $d['label'] }}</x-badge></td>
                                <td class="text-right">
                                    @can('update', $membership)
                                        @if (in_array($membership->status, [\App\Enums\MembershipStatus::Active, \App\Enums\MembershipStatus::Expired], true))
                                            <x-button size="sm" variant="secondary" :href="route('memberships.renew', $membership)">Renew</x-button>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $memberships->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
