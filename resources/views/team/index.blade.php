<x-layouts.app title="Team">
    <x-page-header title="Team" :description="'Owners, front-desk staff and trainers · '.$usage['used'].($usage['limit'] ? ' of '.$usage['limit'].' seats used' : ' seats used')">
        <x-slot:actions><x-button icon="plus" :href="route('staff.create')">Invite team member</x-button></x-slot:actions>
    </x-page-header>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['' => 'Everyone', 'owner' => 'Owners', 'staff' => 'Staff', 'trainer' => 'Trainers'] as $value => $label)
            <a href="{{ route('staff.index', array_filter(['role' => $value, 'status' => $filters['status'] ?? null])) }}"
               @class(['rounded-full px-3.5 py-1.5 text-sm font-medium', 'bg-brand-600 text-white' => ($filters['role'] ?? '') === $value, 'bg-white ring-1 ring-zinc-200 hover:bg-zinc-50 dark:bg-zinc-900 dark:ring-zinc-800' => ($filters['role'] ?? '') !== $value])>{{ $label }}</a>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($users as $member)
            @php $profile = $member->staffProfile; $role = $member->primaryRole(); @endphp
            <div @class(['card p-5', 'opacity-60' => ! $member->is_active])>
                <div class="flex items-start gap-4">
                    <x-avatar :user="$member" size="size-12" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ $member->name }}</p>
                        <p class="truncate text-sm text-zinc-500">{{ $profile?->job_title ?? $role?->label() }}</p>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <x-badge :color="$role === \App\Enums\Role::Owner ? 'brand' : ($role === \App\Enums\Role::Trainer ? 'sky' : 'zinc')">{{ $role?->label() }}</x-badge>
                            @unless ($member->is_active)<x-badge color="rose">Deactivated</x-badge>@endunless
                            @if (! $member->last_login_at && $member->is_active)<x-badge color="amber">Invited</x-badge>@endif
                        </div>
                    </div>
                </div>
                @if ($profile?->specialization)<p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $profile->specialization }}</p>@endif
                <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-zinc-100 pt-4 text-sm dark:border-zinc-800">
                    <div><dt class="text-xs text-zinc-500">Assigned members</dt><dd class="font-semibold tabular-nums">{{ $member->assigned_members_count }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Classes this week</dt><dd class="font-semibold tabular-nums">{{ $upcomingClasses[$member->id] ?? 0 }}</dd></div>
                    <div class="col-span-2"><dt class="text-xs text-zinc-500">Contact</dt><dd class="truncate">{{ $member->email }}@if ($member->phone) · {{ $member->phone }}@endif</dd></div>
                    @if ($profile?->working_hours)
                        <div class="col-span-2"><dt class="text-xs text-zinc-500">Works</dt><dd class="text-xs">{{ collect($profile->working_hours)->map(fn ($s, $d) => ucfirst(substr($d, 0, 3)).' '.$s['start'].'–'.$s['end'])->join(', ') }}</dd></div>
                    @endif
                </dl>
                <div class="mt-4 flex justify-end gap-1">
                    <x-button size="sm" variant="ghost" :href="route('staff.edit', $member)">Edit</x-button>
                    @unless ($member->is(auth()->user()))
                        <x-confirm :action="route('staff.toggle', $member)" method="PATCH" :title="$member->is_active ? 'Deactivate '.$member->name.'?' : 'Reactivate '.$member->name.'?'"
                                   :message="$member->is_active ? 'They will be signed out immediately and can no longer log in.' : 'They will be able to sign in again.'"
                                   :trigger="$member->is_active ? 'Deactivate' : 'Reactivate'" :confirm="$member->is_active ? 'Deactivate' : 'Reactivate'" :variant="$member->is_active ? 'danger' : 'primary'" />
                    @endunless
                </div>
            </div>
        @empty
            <div class="card md:col-span-2 xl:col-span-3"><x-empty-state icon="shield" title="No team members match" /></div>
        @endforelse
    </div>
</x-layouts.app>
