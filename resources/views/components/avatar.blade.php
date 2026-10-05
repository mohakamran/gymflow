@props(['user' => null, 'src' => null, 'initials' => null, 'size' => 'size-9'])
@php
    $src ??= $user?->avatar_url;
    $initials ??= $user?->initials ?? '?';
@endphp
@if ($src)
    <img src="{{ $src }}" alt="" {{ $attributes->class([$size, 'shrink-0 rounded-full object-cover ring-2 ring-white dark:ring-zinc-900']) }}>
@else
    <span {{ $attributes->class([$size, 'grid shrink-0 place-items-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700 ring-2 ring-white dark:bg-brand-500/20 dark:text-brand-200 dark:ring-zinc-900']) }}>{{ $initials }}</span>
@endif
