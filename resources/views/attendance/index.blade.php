<x-layouts.app title="Check-in">
    <x-page-header title="Front desk" :description="tenant_now()->format('l, F j').' · '.$visits->count().' visits today'">
        <x-slot:actions>
            <x-button variant="secondary" icon="clock" :href="route('attendance.history')">History</x-button>
            <x-button variant="secondary" icon="qr" :href="route('attendance.kiosk')" target="_blank">Open kiosk</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[340px_1fr]">
        <div class="space-y-6">
            <x-card title="Check a member in" description="Search, or scan a member card with a QR/barcode scanner.">
                <form method="POST" action="{{ route('attendance.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="toggle" value="1">
                    <x-member-picker label="" :required="false" autofocus />
                    <x-button class="w-full" icon="check">Check in / out</x-button>
                </form>
                <div class="my-5 flex items-center gap-3 text-xs text-zinc-400"><span class="h-px flex-1 bg-zinc-200 dark:bg-zinc-800"></span>or scan<span class="h-px flex-1 bg-zinc-200 dark:bg-zinc-800"></span></div>
                <form method="POST" action="{{ route('attendance.store') }}" class="flex gap-2">
                    @csrf
                    <input type="hidden" name="toggle" value="1">
                    <input type="text" name="code" placeholder="Scan or type code" class="form-control font-mono" autocomplete="off" aria-label="Member code">
                    <x-button variant="secondary">Go</x-button>
                </form>
            </x-card>

            <div class="grid grid-cols-2 gap-4">
                <x-stat label="In the gym" :value="$inGym" icon="users" />
                <x-stat label="Visits today" :value="$visits->count()" icon="qr" />
            </div>
        </div>

        <x-card title="Today's visits" :padding="false" class="min-w-0">
            @if ($visits->isEmpty())
                <x-empty-state icon="qr" title="No check-ins yet today" description="Check-ins appear here as members arrive." />
            @else
                <div class="overflow-x-auto">
                    <table class="table-default">
                        <thead><tr><th>Member</th><th>In</th><th>Out</th><th class="hidden 2xl:table-cell">Membership</th><th class="hidden sm:table-cell">Method</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($visits as $visit)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <x-avatar :src="$visit->member->photo_url" :initials="$visit->member->initials" size="size-8" />
                                            <a href="{{ route('members.show', $visit->member) }}" class="font-medium whitespace-nowrap hover:text-brand-600">{{ $visit->member->full_name }}</a>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap tabular-nums">{{ format_time($visit->checked_in_at) }}</td>
                                    <td class="whitespace-nowrap tabular-nums">
                                        @if ($visit->checked_out_at)
                                            {{ format_time($visit->checked_out_at) }}
                                        @else
                                            <x-badge color="emerald"><span class="size-1.5 rounded-full bg-emerald-500"></span> In gym</x-badge>
                                        @endif
                                    </td>
                                    <td class="hidden 2xl:table-cell">
                                        @php $m = $visit->member->currentMembership; @endphp
                                        @if ($m)<span class="text-sm">{{ $m->plan->name }}</span><p class="text-xs text-zinc-500">{{ $m->daysRemaining() }} days left</p>@else<span class="text-zinc-400">—</span>@endif
                                    </td>
                                    <td class="hidden whitespace-nowrap sm:table-cell">{{ $visit->method->label() }}</td>
                                    <td class="text-right">
                                        @unless ($visit->checked_out_at)
                                            <form method="POST" action="{{ route('attendance.checkout', $visit) }}">@csrf @method('PATCH')<x-button size="sm" variant="secondary">Check out</x-button></form>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app>
