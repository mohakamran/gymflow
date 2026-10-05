@props(['title', 'description' => null])
<div {{ $attributes->class(['mb-6 flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $title }}</h1>
        @if ($description)<p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>@endif
    </div>
    @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
</div>
