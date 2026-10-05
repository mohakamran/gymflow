<x-layouts.app title="Members">
    @php $canManage = auth()->user()->can(\App\Enums\Permission::MembersManage); @endphp
    <x-page-header title="Members" :description="$canManage ? number_format($counts['active']).' active of '.number_format($counts['all']).' members' : 'Members assigned to you'">
        @if ($canManage)
            <x-slot:actions><x-button icon="plus" :href="route('members.create')">Add member</x-button></x-slot:actions>
        @endif
    </x-page-header>

    <x-card :padding="false">
        <x-filters :reset="route('members.index')">
            <div class="relative min-w-56 flex-1">
                <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-zinc-400" />
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, code, email or phone" class="form-control pl-9" aria-label="Search members">
            </div>
            <select name="membership" class="form-control w-auto" aria-label="Membership">
                <option value="">Any membership</option>
                @foreach (['active' => 'Active', 'expiring' => 'Expiring (7 days)', 'expired' => 'Expired', 'none' => 'Never purchased'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['membership'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="plan" class="form-control w-auto" aria-label="Plan">
                <option value="">Any plan</option>
                @foreach ($plans as $id => $name)<option value="{{ $id }}" @selected((string) ($filters['plan'] ?? '') === (string) $id)>{{ $name }}</option>@endforeach
            </select>
            <select name="status" class="form-control w-auto" aria-label="Status">
                <option value="">Any status</option>
                @foreach (\App\Enums\MemberStatus::options() as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach
            </select>
            @if ($canManage && $trainers->isNotEmpty())
                <select name="trainer" class="form-control w-auto" aria-label="Trainer">
                    <option value="">Any trainer</option>
                    @foreach ($trainers as $id => $name)<option value="{{ $id }}" @selected((string) ($filters['trainer'] ?? '') === (string) $id)>{{ $name }}</option>@endforeach
                </select>
            @endif
            <select name="sort" class="form-control w-auto" aria-label="Sort">
                @foreach (['newest' => 'Newest first', 'oldest' => 'Oldest first', 'name' => 'Name A–Z', 'code' => 'Member code'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['sort'] ?? 'newest') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-filters>

        @if ($members->isEmpty())
            <x-empty-state icon="users" title="{{ array_filter($filters) ? 'No members match these filters' : 'No members yet' }}" description="{{ array_filter($filters) ? 'Try clearing a filter.' : 'Add your first member to start selling memberships and tracking check-ins.' }}">
                @if ($canManage && ! array_filter($filters))<x-button icon="plus" :href="route('members.create')">Add member</x-button>@endif
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="table-default">
                    <thead><tr><th>Member</th><th class="hidden md:table-cell">Contact</th><th>Membership</th><th class="hidden lg:table-cell">Trainer</th><th class="hidden sm:table-cell">Joined</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($members as $member)
                            @php $current = $member->currentMembership; $display = $current?->displayStatus(); @endphp
                            <tr class="cursor-pointer" onclick="window.location='{{ route('members.show', $member) }}'">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-avatar :src="$member->photo_url" :initials="$member->initials" />
                                        <div class="min-w-0">
                                            <a href="{{ route('members.show', $member) }}" class="block truncate font-medium text-zinc-900 hover:text-brand-600 dark:text-white">{{ $member->full_name }}</a>
                                            <p class="text-xs text-zinc-500">{{ $member->member_code }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell"><p class="truncate">{{ $member->email ?? '—' }}</p><p class="text-xs text-zinc-500">{{ $member->phone }}</p></td>
                                <td>
                                    @if ($current)
                                        <p class="font-medium">{{ $current->plan->name }}</p>
                                        <p class="text-xs text-zinc-500">until {{ format_date($current->ends_on) }}</p>
                                    @else
                                        <span class="text-zinc-400">None</span>
                                    @endif
                                </td>
                                <td class="hidden lg:table-cell">{{ $member->trainer?->name ?? '—' }}</td>
                                <td class="hidden whitespace-nowrap sm:table-cell">{{ format_date($member->joined_on) }}</td>
                                <td>
                                    @if ($member->status !== \App\Enums\MemberStatus::Active)
                                        <x-status-badge :status="$member->status" />
                                    @elseif ($display)
                                        <x-badge :color="$display['color']">{{ $display['label'] }}</x-badge>
                                    @else
                                        <x-badge>No plan</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $members->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
