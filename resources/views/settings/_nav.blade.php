@php
    $tabs = [
        ['settings.profile.edit', 'Gym profile', 'building'],
        ['settings.branding.edit', 'Branding', 'swatch'],
        ['settings.hours.edit', 'Business hours', 'clock'],
        ['settings.localization.edit', 'Regional, tax & invoices', 'globe'],
        ['settings.notifications.edit', 'Notifications', 'bell'],
        ['settings.billing.edit', 'Plan & billing', 'card'],
    ];
@endphp
<nav class="-mx-4 mb-6 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Settings">
    <div class="flex min-w-max gap-1 rounded-xl border border-zinc-200 bg-white p-1 dark:border-zinc-800 dark:bg-zinc-900">
        @foreach ($tabs as [$route, $label, $icon])
            @php $active = request()->routeIs($route); @endphp
            <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
               @class(['flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                   'bg-brand-600 text-white shadow-sm' => $active,
                   'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' => ! $active])>
                <x-icon :name="$icon" class="size-4" /> {{ $label }}
            </a>
        @endforeach
    </div>
</nav>
