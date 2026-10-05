<x-layouts.app :title="$plan->title">
    <x-page-header :title="$plan->title">
        <x-slot:actions>
            <x-button variant="secondary" icon="arrow-left" :href="route('portal.workouts')">Back</x-button>
            <x-button variant="secondary" icon="printer" type="button" onclick="window.print()">Print</x-button>
        </x-slot:actions>
    </x-page-header>
    @include('workouts._plan')
</x-layouts.app>
