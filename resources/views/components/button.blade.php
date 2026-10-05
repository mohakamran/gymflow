@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'icon' => null])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 disabled:pointer-events-none disabled:opacity-60 whitespace-nowrap';
    $sizes = ['sm' => 'px-2.5 py-1.5 text-xs', 'md' => 'px-3.5 py-2 text-sm', 'lg' => 'px-5 py-2.5 text-sm'];
    $variants = [
        'primary' => 'bg-brand-600 text-white shadow-sm shadow-brand-600/20 hover:bg-brand-700',
        'secondary' => 'bg-white text-zinc-800 ring-1 ring-zinc-300 ring-inset shadow-xs hover:bg-zinc-50 dark:bg-zinc-900 dark:text-zinc-100 dark:ring-zinc-700 dark:hover:bg-zinc-800',
        'ghost' => 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white',
        'danger' => 'bg-rose-600 text-white shadow-sm hover:bg-rose-700',
    ];
    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </button>
@endif
