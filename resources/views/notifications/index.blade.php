<x-layouts.app title="Notifications">
    <x-page-header title="Notifications">
        <x-slot:actions>
            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<x-button variant="secondary" size="sm">Mark all as read</x-button></form>
        </x-slot:actions>
    </x-page-header>
    <x-card :padding="false">
        @include('notifications._list', ['readRoute' => 'notifications.read'])
    </x-card>
    <div class="mt-4">{{ $notifications->links() }}</div>
</x-layouts.app>
