<x-layouts.app :title="$item->name">
    <div class="mb-4"><a href="{{ route('equipment.index') }}" class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200"><x-icon name="arrow-left" class="size-4" /> Equipment</a></div>
    <x-page-header :title="$item->name" :description="$item->category->label().($item->location ? ' · '.$item->location : '')">
        <x-slot:actions>
            <x-status-badge :status="$item->status" />
            <x-button variant="secondary" icon="pencil" :href="route('equipment.edit', $item)">Edit</x-button>
            <x-confirm :action="route('equipment.destroy', $item)" method="DELETE" title="Remove {{ $item->name }}?" trigger="Remove" confirm="Remove" size="md" triggerVariant="secondary" />
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <x-card title="Details">
                <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-3">
                    @foreach (['Quantity' => $item->quantity, 'Condition' => $item->condition->label(), 'Serial number' => $item->serial_number, 'Purchased' => format_date($item->purchased_on), 'Unit cost' => $item->cost !== null ? money($item->cost) : null, 'Total value' => $item->cost !== null ? money($item->cost * $item->quantity) : null, 'Last maintained' => format_date($item->last_maintained_on), 'Next maintenance' => format_date($item->next_maintenance_on)] as $label => $value)
                        <div><dt class="text-zinc-500">{{ $label }}</dt><dd class="mt-0.5 font-medium">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                    @if ($item->notes)<div class="sm:col-span-3"><dt class="text-zinc-500">Notes</dt><dd class="mt-0.5 whitespace-pre-line">{{ $item->notes }}</dd></div>@endif
                </dl>
                @if ($item->isMaintenanceOverdue())<x-alert type="warning" class="mt-5">Maintenance is overdue since {{ format_date($item->next_maintenance_on) }}.</x-alert>@endif
            </x-card>
            <x-card title="Maintenance history" :padding="false">
                @if ($item->maintenances->isEmpty())
                    <x-empty-state icon="wrench" title="No maintenance logged" />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-default">
                            <thead><tr><th>Date</th><th>Type</th><th>By</th><th class="text-right">Cost</th><th>Notes</th></tr></thead>
                            <tbody>
                                @foreach ($item->maintenances as $log)
                                    <tr><td class="whitespace-nowrap">{{ format_date($log->performed_on) }}</td><td>{{ $types[$log->type] ?? $log->type }}</td><td>{{ $log->performed_by ?? '—' }}</td><td class="text-right tabular-nums">{{ $log->cost !== null ? money($log->cost) : '—' }}</td><td class="text-zinc-500">{{ $log->notes }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>
        <form method="POST" action="{{ route('equipment.maintenance.store', $item) }}">
            @csrf
            <x-card title="Log maintenance">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <x-input name="performed_on" type="date" label="Date" :value="tenant_today()->toDateString()" required />
                        <x-select name="type" label="Type" :options="$types" value="service" required />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <x-input name="cost" type="number" step="0.01" min="0" label="Cost" />
                        <x-input name="performed_by" label="Performed by" />
                    </div>
                    <x-textarea name="notes" label="Notes" rows="2" />
                    <div class="grid grid-cols-2 gap-4">
                        <x-select name="status" label="Status after" :options="\App\Enums\EquipmentStatus::options()" value="active" required />
                        <x-select name="condition" label="Condition" :options="\App\Enums\EquipmentCondition::options()" :value="$item->condition->value" required />
                    </div>
                    <x-input name="next_maintenance_on" type="date" label="Next maintenance" :value="tenant_today()->addMonths(3)->toDateString()" />
                </div>
                <x-slot:footer><x-button icon="wrench">Log maintenance</x-button></x-slot:footer>
            </x-card>
        </form>
    </div>
</x-layouts.app>
