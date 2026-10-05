@props(['label' => null, 'for' => null, 'error' => null, 'hint' => null, 'required' => false])
@php $errorKey = $error ?? $for; @endphp
<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label for="{{ $for }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}@if ($required)<span class="text-rose-500"> *</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if ($errorKey && $errors->has($errorKey))
        <p class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ $errors->first($errorKey) }}</p>
    @elseif ($hint)
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
