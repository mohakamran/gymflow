@props(['type' => 'info'])
@php
    $styles = [
        'info' => ['bg-sky-50 text-sky-800 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-200 dark:ring-sky-500/20', 'info'],
        'success' => ['bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/20', 'check-circle'],
        'warning' => ['bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/20', 'exclamation'],
        'error' => ['bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-500/20', 'exclamation'],
    ];
    [$classes, $icon] = $styles[$type] ?? $styles['info'];
@endphp
<div role="status" {{ $attributes->class(['flex gap-3 rounded-xl p-3.5 text-sm ring-1 ring-inset', $classes]) }}>
    <x-icon :name="$icon" class="size-5 shrink-0" />
    <div class="min-w-0">{{ $slot }}</div>
</div>
