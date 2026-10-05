<x-layouts.app title="Notifications">
    <x-page-header title="Notifications" />
    <x-card :padding="false">@include('notifications._list')</x-card>
    <div class="mt-4">{{ $notifications->links() }}</div>
</x-layouts.app>
