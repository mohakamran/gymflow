<x-layouts.app :title="$session->gymClass->name">
    @php
        $canManage = auth()->user()->can('update', $session);
        $canBook = auth()->user()->can(\App\Enums\Permission::AttendanceManage);
        $booked = $session->bookings->whereIn('status', [\App\Enums\BookingStatus::Booked, \App\Enums\BookingStatus::Attended])->count();
    @endphp
    <div class="mb-4"><a href="{{ route('classes.index', ['week' => $session->starts_at->copy()->setTimezone(tenant_timezone())->toDateString()]) }}" class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200"><x-icon name="arrow-left" class="size-4" /> Schedule</a></div>
    <x-page-header :title="$session->gymClass->name" :description="format_date($session->starts_at, true).' – '.format_time($session->ends_at).($session->location ? ' · '.$session->location : '')">
        <x-slot:actions>
            <x-status-badge :status="$session->status" />
            @if ($canManage && $session->status === \App\Enums\ClassSessionStatus::Scheduled)
                <x-confirm :action="route('classes.sessions.cancel', $session)" title="Cancel this session?" message="All bookings are released. Members can see the cancellation in their portal." trigger="Cancel session" confirm="Cancel session" size="md" triggerVariant="secondary">
                    @if ($seriesCount > 1)
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="series" value="1" class="form-checkbox"> Also cancel the following {{ $seriesCount - 1 }} sessions in this series</label>
                    @endif
                </x-confirm>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <x-card title="Roster" :description="$booked.' of '.$session->capacity.' spots taken'" :padding="false">
            @if ($session->bookings->isEmpty())
                <x-empty-state icon="users" title="No bookings yet" />
            @else
                <div class="overflow-x-auto">
                    <table class="table-default">
                        <thead><tr><th>Member</th><th>Booked</th><th>Status</th>@if ($canBook)<th class="text-right">Mark</th>@endif</tr></thead>
                        <tbody>
                            @foreach ($session->bookings as $booking)
                                <tr>
                                    <td><a href="{{ route('members.show', $booking->member) }}" class="font-medium hover:text-brand-600">{{ $booking->member->full_name }}</a></td>
                                    <td class="text-xs text-zinc-500">{{ $booking->created_at->diffForHumans() }}</td>
                                    <td><x-status-badge :status="$booking->status" /></td>
                                    @if ($canBook)
                                        <td class="text-right whitespace-nowrap">
                                            @if ($booking->status !== \App\Enums\BookingStatus::Cancelled)
                                                @foreach (['attended' => 'Attended', 'no_show' => 'No-show', 'cancelled' => 'Cancel'] as $status => $label)
                                                    @if ($booking->status->value !== $status && ! ($status === 'cancelled' && $booking->status !== \App\Enums\BookingStatus::Booked))
                                                        <form method="POST" action="{{ route('classes.bookings.update', $booking) }}" class="inline">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $status }}"><x-button size="sm" variant="ghost">{{ $label }}</x-button></form>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <div class="space-y-6">
            <x-card title="Details">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-zinc-500">Trainer</dt><dd class="font-medium">{{ $session->trainer?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Capacity</dt><dd class="font-medium">{{ $session->capacity }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Spots left</dt><dd class="font-medium">{{ max(0, $session->capacity - $booked) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Member booking</dt><dd class="font-medium">{{ $session->gymClass->allow_member_booking ? 'Open' : 'Staff only' }}</dd></div>
                </dl>
                @if ($session->gymClass->description)<p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $session->gymClass->description }}</p>@endif
            </x-card>
            @if ($canBook && $session->isBookable())
                <form method="POST" action="{{ route('classes.bookings.store', $session) }}">
                    @csrf
                    <x-card title="Book a member">
                        <x-member-picker label="" />
                        <x-slot:footer><x-button>Book</x-button></x-slot:footer>
                    </x-card>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
