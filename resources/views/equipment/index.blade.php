<x-layouts.app title="Equipment">
    <x-page-header title="Equipment" :description="'Inventory value '.money($assetValue)">
        <x-slot:actions><x-button icon="plus" :href="route('equipment.create')">Add equipment</x-button></x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach (\App\Enums\EquipmentStatus::cases() as $status)
            <a href="{{ route('equipment.index', ['status' => $status->value]) }}" class="card p-4 hover:border-brand-200">
                <p class="flex items-center gap-2 text-xs text-zinc-500"><span class="size-2 rounded-full bg-{{ $status->color() }}-500"></span>{{ $status->label() }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums">{{ (int) ($statusCounts[$status->value] ?? 0) }}</p>
            </a>
        @endforeach
        <a href="{{ route('equipment.index', ['maintenance' => 'due']) }}" class="card p-4 hover:border-brand-200">
            <p class="flex items-center gap-2 text-xs text-zinc-500"><x-icon name="wrench" class="size-3.5" /> Maintenance due</p>
            <p class="mt-1 text-xl font-semibold tabular-nums">{{ $dueCount }}</p>
        </a>
    </div>

    <x-card :padding="false">
        <x-filters :reset="route('equipment.index')">
            <x-input name="search" type="search" label="Search" :value="$filters['search'] ?? null" placeholder="Name, serial or location" />
            <x-select name="category" label="Category" :options="\App\Enums\EquipmentCategory::options()" :value="$filters['category'] ?? null" placeholder="All" />
            <x-select name="status" label="Status" :options="\App\Enums\EquipmentStatus::options()" :value="$filters['status'] ?? null" placeholder="All" />
        </x-filters>
        @if ($equipment->isEmpty())
            <x-empty-state icon="wrench" title="No equipment found" />
        @else
            <div class="overflow-x-auto">
                <table class="table-default">
                    <thead><tr><th>Equipment</th><th class="hidden md:table-cell">Category</th><th>Qty</th><th class="hidden lg:table-cell">Location</th><th>Status</th><th>Next maintenance</th></tr></thead>
                    <tbody>
                        @foreach ($equipment as $item)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('equipment.show', $item) }}'">
                                <td><a href="{{ route('equipment.show', $item) }}" class="font-medium hover:text-brand-600">{{ $item->name }}</a>@if ($item->serial_number)<p class="font-mono text-xs text-zinc-500">{{ $item->serial_number }}</p>@endif</td>
                                <td class="hidden md:table-cell">{{ $item->category->label() }}</td>
                                <td class="tabular-nums">{{ $item->quantity }}</td>
                                <td class="hidden lg:table-cell">{{ $item->location ?? '—' }}</td>
                                <td><x-status-badge :status="$item->status" /></td>
                                <td @class(['whitespace-nowrap', 'font-semibold text-rose-600' => $item->isMaintenanceOverdue()])>{{ format_date($item->next_maintenance_on) }}@if ($item->isMaintenanceOverdue()) · overdue @endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $equipment->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
