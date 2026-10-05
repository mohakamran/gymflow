<x-layouts.app title="Users">
    <x-page-header title="Users" description="Every account on the platform." />
    <x-card :padding="false">
        <x-filters :reset="route('admin.users.index')">
            <x-input name="search" type="search" label="Search" :value="$filters['search'] ?? null" placeholder="Name or email" />
            <x-select name="role" label="Role" :options="collect(\App\Enums\Role::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])" :value="$filters['role'] ?? null" placeholder="Any" />
        </x-filters>
        <div class="overflow-x-auto">
            <table class="table-default">
                <thead><tr><th>User</th><th>Gym</th><th>Role</th><th class="hidden md:table-cell">Last login</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($users as $account)
                        <tr>
                            <td><div class="flex items-center gap-3"><x-avatar :user="$account" size="size-8" /><div><p class="font-medium">{{ $account->name }}</p><p class="text-xs text-zinc-500">{{ $account->email }}</p></div></div></td>
                            <td>@if ($account->tenant)<a href="{{ route('admin.tenants.show', $account->tenant) }}" class="hover:text-brand-600">{{ $account->tenant->name }}</a>@else<span class="text-zinc-400">Platform</span>@endif</td>
                            <td>{{ $account->primaryRole()?->label() }}</td>
                            <td class="hidden md:table-cell">{{ $account->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                            <td>@if ($account->is_active)<x-badge color="emerald">Active</x-badge>@else<x-badge color="rose">Disabled</x-badge>@endif</td>
                            <td class="text-right">
                                @unless ($account->is(auth()->user()))
                                    <x-confirm :action="route('admin.users.toggle', $account->id)" method="PATCH" :title="($account->is_active ? 'Disable ' : 'Enable ').$account->name.'?'" :trigger="$account->is_active ? 'Disable' : 'Enable'" :confirm="$account->is_active ? 'Disable' : 'Enable'" :variant="$account->is_active ? 'danger' : 'primary'" />
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $users->links() }}</div>
    </x-card>
</x-layouts.app>
