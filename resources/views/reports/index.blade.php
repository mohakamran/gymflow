<x-layouts.app title="Reports">
    <x-page-header title="Reports" description="Filter by date range, export to CSV/Excel or PDF, or print." />
    @php
        $icons = ['revenue' => 'chart', 'expenses' => 'receipt', 'profit' => 'sparkles', 'payments' => 'card', 'memberships' => 'card', 'expired' => 'clock', 'members' => 'users', 'attendance' => 'qr', 'trainers' => 'shield', 'classes' => 'calendar', 'equipment' => 'wrench'];
        $groups = ['Finance' => ['revenue', 'expenses', 'profit', 'payments'], 'Members' => ['memberships', 'expired', 'members', 'attendance'], 'Operations' => ['trainers', 'classes', 'equipment']];
    @endphp
    @foreach ($groups as $group => $keys)
        <h2 class="mt-8 mb-3 text-xs font-semibold tracking-wider text-zinc-400 uppercase first:mt-0">{{ $group }}</h2>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($keys as $key)
                <a href="{{ route('reports.show', $key) }}" class="card group p-5 transition hover:border-brand-200 hover:shadow-md dark:hover:border-brand-500/30">
                    <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300"><x-icon :name="$icons[$key]" /></span>
                    <h3 class="mt-4 font-semibold group-hover:text-brand-600">{{ $types[$key]['label'] }}</h3>
                    <p class="mt-1 text-sm text-zinc-500">{{ $types[$key]['description'] }}</p>
                </a>
            @endforeach
        </div>
    @endforeach
</x-layouts.app>
