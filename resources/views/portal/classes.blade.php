<x-layouts.app title="Book classes">
    <x-page-header title="Book classes" description="The next 14 days." />
    @forelse ($sessionsByDay as $date => $sessions)
        <h2 class="mt-6 mb-3 text-sm font-semibold first:mt-0">{{ \Carbon\Carbon::parse($date)->format('l, F j') }}</h2>
        <div class="space-y-3">
            @foreach ($sessions as $session)
                @php $bookingId = $myBookings[$session->id] ?? null; $left = $session->spotsLeft(); @endphp
                <div class="card flex flex-wrap items-center gap-4 p-4">
                    <span class="h-12 w-1.5 rounded-full" style="background: {{ $session->gymClass->color }}"></span>
                    <div class="min-w-40 flex-1">
                        <p class="font-semibold">{{ $session->gymClass->name }}</p>
                        <p class="text-sm text-zinc-500">{{ format_time($session->starts_at) }} – {{ format_time($session->ends_at) }} · {{ $session->trainer?->name ?? 'TBA' }}@if ($session->location) · {{ $session->location }}@endif</p>
                    </div>
                    <span @class(['text-xs font-medium', 'text-rose-600' => $left === 0, 'text-amber-600' => $left > 0 && $left <= 3, 'text-zinc-500' => $left > 3])>{{ $left === 0 ? 'Full' : $left.' spots left' }}</span>
                    @if ($bookingId)
                        <form method="POST" action="{{ route('portal.bookings.cancel', $bookingId) }}">@csrf @method('DELETE')<x-button size="sm" variant="secondary">Cancel booking</x-button></form>
                        <x-badge color="emerald">Booked</x-badge>
                    @elseif (! $session->gymClass->allow_member_booking)
                        <span class="text-xs text-zinc-500">Book at front desk</span>
                    @elseif ($left > 0)
                        <form method="POST" action="{{ route('portal.classes.book', $session) }}">@csrf<x-button size="sm">Book</x-button></form>
                    @endif
                </div>
            @endforeach
        </div>
    @empty
        <div class="card"><x-empty-state icon="calendar" title="No classes scheduled in the next two weeks" /></div>
    @endforelse
</x-layouts.app>
