<x-layouts.app title="Class schedule">
    @php $canManage = auth()->user()->can(\App\Enums\Permission::ClassesManage); @endphp
    <x-page-header title="Class schedule" :description="$weekStart->format('M j').' – '.$weekStart->endOfWeek()->format('M j, Y').' · '.$totalBooked.' booked of '.$totalCapacity.' spots'">
        <x-slot:actions>
            @if ($canManage)
                <x-button variant="secondary" :href="route('classes.types.index')">Class types</x-button>
                <x-button icon="plus" :href="route('classes.sessions.create')">Schedule class</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <x-button size="sm" variant="secondary" :href="route('classes.index', array_merge(request()->query(), ['week' => $weekStart->subWeek()->toDateString()]))" aria-label="Previous week"><x-icon name="arrow-left" class="size-4" /></x-button>
        <x-button size="sm" variant="secondary" :href="route('classes.index', array_merge(request()->query(), ['week' => tenant_today()->toDateString()]))">This week</x-button>
        <x-button size="sm" variant="secondary" :href="route('classes.index', array_merge(request()->query(), ['week' => $weekStart->addWeek()->toDateString()]))" aria-label="Next week"><x-icon name="arrow-right" class="size-4" /></x-button>
        @if ($canManage && $trainers->isNotEmpty())
            <form method="GET" class="ml-auto">
                <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                <select name="trainer" class="form-control w-auto" onchange="this.form.submit()" aria-label="Trainer">
                    <option value="">All trainers</option>
                    @foreach ($trainers as $id => $name)<option value="{{ $id }}" @selected(request('trainer') == $id)>{{ $name }}</option>@endforeach
                </select>
            </form>
        @endif
    </div>

    <div class="grid gap-3 md:grid-cols-7">
        @foreach ($days as $day)
            @php $sessions = $sessionsByDay[$day->toDateString()] ?? collect(); $isToday = $day->isSameDay(tenant_today()); @endphp
            <div @class(['card min-h-40 p-3', 'ring-2 ring-brand-500' => $isToday])>
                <div class="mb-3 flex items-baseline justify-between">
                    <p class="text-xs font-semibold tracking-wider text-zinc-500 uppercase">{{ $day->format('D') }}</p>
                    <p @class(['text-lg font-semibold', 'text-brand-600' => $isToday])>{{ $day->format('j') }}</p>
                </div>
                <div class="space-y-2">
                    @forelse ($sessions as $session)
                        @php $cancelled = $session->status === \App\Enums\ClassSessionStatus::Cancelled; @endphp
                        <a href="{{ route('classes.sessions.show', $session) }}" @class(['block rounded-lg border-l-4 bg-zinc-50 p-2 text-xs transition hover:bg-zinc-100 dark:bg-zinc-800/60 dark:hover:bg-zinc-800', 'opacity-50 line-through' => $cancelled]) style="border-color: {{ $session->gymClass->color }}">
                            <p class="font-semibold text-zinc-900 dark:text-white">{{ $session->gymClass->name }}</p>
                            <p class="text-zinc-500">{{ format_time($session->starts_at) }} · {{ $session->trainer?->name ?? '—' }}</p>
                            <p class="mt-1 tabular-nums text-zinc-500">{{ $session->active_bookings_count }}/{{ $session->capacity }} booked</p>
                        </a>
                    @empty
                        <p class="text-xs text-zinc-400">No classes</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.app>
