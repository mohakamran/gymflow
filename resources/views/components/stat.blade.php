@props(['label', 'value', 'icon' => null, 'hint' => null])
<div {{ $attributes->class(['card p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
        @if ($icon)
            <span class="grid size-9 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300"><x-icon :name="$icon" class="size-5" /></span>
        @endif
    </div>
    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-900 tabular-nums dark:text-white">{{ $value }}</p>
    @if ($hint)<p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>@endif
</div>
