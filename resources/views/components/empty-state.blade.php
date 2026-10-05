@props(['icon' => 'sparkles', 'title', 'description' => null])
<div {{ $attributes->class(['flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="grid size-12 place-items-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"><x-icon :name="$icon" class="size-6" /></span>
    <h3 class="mt-4 text-sm font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>
    @if ($description)<p class="mt-1 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>@endif
    @if ($slot->isNotEmpty())<div class="mt-5">{{ $slot }}</div>@endif
</div>
