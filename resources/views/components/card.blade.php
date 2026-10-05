@props(['title' => null, 'description' => null, 'padding' => true])
<section {{ $attributes->class(['card']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
            <div>
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $title }}</h2>
                @if ($description)<p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div @class(['p-5' => $padding])>{{ $slot }}</div>
    @isset($footer)
        <footer class="flex flex-wrap items-center justify-end gap-2 rounded-b-2xl border-t border-zinc-100 bg-zinc-50/60 px-5 py-3 dark:border-zinc-800 dark:bg-zinc-900/60">{{ $footer }}</footer>
    @endisset
</section>
