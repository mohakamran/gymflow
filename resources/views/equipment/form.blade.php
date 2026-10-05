@php $editing = $item->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit '.$item->name : 'Add equipment'">
    <x-page-header :title="$editing ? 'Edit '.$item->name : 'Add equipment'">
        <x-slot:actions><x-button variant="secondary" icon="arrow-left" :href="$editing ? route('equipment.show', $item) : route('equipment.index')">Back</x-button></x-slot:actions>
    </x-page-header>
    <form method="POST" action="{{ $editing ? route('equipment.update', $item) : route('equipment.store') }}" class="max-w-3xl">
        @csrf
        @if ($editing) @method('PUT') @endif
        <x-card>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><x-input name="name" label="Name" :value="$item->name" placeholder="e.g. Concept2 Rower" required autofocus /></div>
                <x-select name="category" label="Category" :options="\App\Enums\EquipmentCategory::options()" :value="$item->category?->value" required />
                <x-input name="quantity" type="number" min="1" label="Quantity" :value="$item->quantity" required />
                <x-input name="serial_number" label="Serial number" :value="$item->serial_number" />
                <x-input name="location" label="Location" :value="$item->location" placeholder="e.g. Cardio zone" />
                <x-input name="purchased_on" type="date" label="Purchase date" :value="$item->purchased_on?->toDateString()" />
                <x-input name="cost" type="number" step="0.01" min="0" :label="'Unit cost ('.tenant()->currency.')'" :value="$item->cost" />
                <x-select name="condition" label="Condition" :options="\App\Enums\EquipmentCondition::options()" :value="$item->condition?->value" required />
                <x-select name="status" label="Status" :options="\App\Enums\EquipmentStatus::options()" :value="$item->status?->value" required />
                <x-input name="next_maintenance_on" type="date" label="Next maintenance" :value="$item->next_maintenance_on?->toDateString()" hint="Owners get a reminder when it's due." />
                <div class="sm:col-span-2"><x-textarea name="notes" label="Notes" :value="$item->notes" rows="2" /></div>
            </div>
            <x-slot:footer><x-button>{{ $editing ? 'Save' : 'Add equipment' }}</x-button></x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
