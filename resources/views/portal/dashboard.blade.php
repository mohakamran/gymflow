<x-layouts.app title="My portal">
    @php $current = $member->currentMembership; $display = $current?->displayStatus(); @endphp
    <x-page-header :title="'Hi, '.$member->first_name.' 👋'" :description="'Welcome back to '.$tenant->name" />

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card overflow-hidden lg:col-span-2">
            <div class="bg-gradient-to-br from-brand-600 to-brand-800 p-6 text-white">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-white/70">Your membership</p>
                        <p class="mt-1 text-2xl font-semibold">{{ $current?->plan?->name ?? 'No active membership' }}</p>
                        @if ($current)<p class="mt-1 text-sm text-white/80">{{ format_date($current->starts_on) }} – {{ format_date($current->ends_on) }}</p>@endif
                    </div>
                    @if ($display)<span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">{{ $display['label'] }}</span>@endif
                </div>
                @if ($current)
                    <div class="mt-6 h-2 overflow-hidden rounded-full bg-white/20"><div class="h-full rounded-full bg-white" style="width: {{ $current->progressPercent() }}%"></div></div>
                    <p class="mt-2 text-sm text-white/80">{{ $current->daysRemaining() }} days remaining</p>
                @else
                    <p class="mt-4 text-sm text-white/80">Visit the front desk to start or renew your membership.</p>
                @endif
            </div>
            <div class="grid grid-cols-3 divide-x divide-zinc-100 dark:divide-zinc-800">
                <div class="p-4 text-center"><p class="text-2xl font-semibold tabular-nums">{{ $visitsThisMonth }}</p><p class="text-xs text-zinc-500">Visits this month</p></div>
                <div class="p-4 text-center"><p class="text-2xl font-semibold tabular-nums">{{ $nextBookings->count() }}</p><p class="text-xs text-zinc-500">Upcoming classes</p></div>
                <div class="p-4 text-center"><p @class(['text-2xl font-semibold tabular-nums', 'text-rose-600' => $balance > 0])>{{ money($balance) }}</p><p class="text-xs text-zinc-500">Balance due</p></div>
            </div>
        </section>

        <x-card title="Check-in pass" description="Show this at the front desk or scan it at the kiosk.">
            <div class="flex flex-col items-center">
                <div class="rounded-2xl bg-white p-3 ring-1 ring-zinc-200"><div x-data="qrCode(@js($member->checkInPayload()), 180)" class="size-[180px] [&_svg]:size-full"></div></div>
                <p class="mt-3 font-mono text-sm tracking-widest text-zinc-500">{{ $member->member_code }}</p>
            </div>
        </x-card>

        <x-card title="Upcoming classes" :padding="false">
            <x-slot:actions><x-button size="sm" variant="ghost" :href="route('portal.classes')">Book</x-button></x-slot:actions>
            @forelse ($nextBookings as $booking)
                <div class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3 last:border-0 dark:border-zinc-800">
                    <span class="h-9 w-1 rounded-full" style="background: {{ $booking->session->gymClass->color }}"></span>
                    <div><p class="text-sm font-medium">{{ $booking->session->gymClass->name }}</p><p class="text-xs text-zinc-500">{{ format_date($booking->session->starts_at, true) }}</p></div>
                </div>
            @empty
                <x-empty-state icon="calendar" title="No classes booked"><x-button size="sm" :href="route('portal.classes')">Browse classes</x-button></x-empty-state>
            @endforelse
        </x-card>

        <x-card title="Your training" :padding="false">
            @if ($activePlan)
                <a href="{{ route('portal.workouts.show', $activePlan) }}" class="block px-5 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                    <p class="font-medium">{{ $activePlan->title }}</p>
                    <p class="text-xs text-zinc-500">{{ $activePlan->goal }} · {{ count($activePlan->exercises ?? []) }} exercises</p>
                </a>
            @else
                <p class="px-5 py-4 text-sm text-zinc-500">No workout plan yet.</p>
            @endif
            @if ($member->trainer)
                <div class="flex items-center gap-3 border-t border-zinc-100 px-5 py-4 dark:border-zinc-800">
                    <x-avatar :user="$member->trainer" />
                    <div><p class="text-sm font-medium">{{ $member->trainer->name }}</p><p class="text-xs text-zinc-500">{{ $member->trainer->staffProfile?->specialization ?? 'Your trainer' }}</p></div>
                </div>
            @endif
        </x-card>

        <x-card title="Announcements" :padding="false">
            <x-slot:actions><x-button size="sm" variant="ghost" :href="route('portal.announcements')">All</x-button></x-slot:actions>
            @forelse ($announcements as $announcement)
                <div class="border-b border-zinc-100 px-5 py-3 last:border-0 dark:border-zinc-800"><p class="text-sm font-medium">{{ $announcement->title }}</p><p class="mt-0.5 line-clamp-2 text-xs text-zinc-500">{{ $announcement->body }}</p></div>
            @empty
                <p class="px-5 py-4 text-sm text-zinc-500">Nothing new.</p>
            @endforelse
        </x-card>
    </div>
</x-layouts.app>
