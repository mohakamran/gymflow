<x-layouts.app :title="$plan->title">
    <x-page-header :title="$plan->title" :description="'Workout plan for '.$member->full_name">
        <x-slot:actions>
            <x-button variant="secondary" icon="arrow-left" :href="route('members.show', $member).'#workouts'">Back</x-button>
            <x-button variant="secondary" icon="printer" type="button" onclick="window.print()">Print</x-button>
            @can('update', $plan)<x-button icon="pencil" :href="route('members.workouts.edit', [$member, $plan])">Edit</x-button>@endcan
        </x-slot:actions>
    </x-page-header>
    @include('workouts._plan')
</x-layouts.app>
