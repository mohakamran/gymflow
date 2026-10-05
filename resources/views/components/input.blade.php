@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false, 'bag' => 'default'])
@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $dotName = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->getBag($bag)->has($dotName);
@endphp
<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}@if ($required)<span class="text-rose-500"> *</span>@endif</label>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
           @if ($type !== 'password' && $type !== 'file') value="{{ old($dotName, $value) }}" @endif
           @required($required)
           @if ($hasError) aria-invalid="true" @endif
           {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $hasError]) }}>
    @if ($hasError)
        <p class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ $errors->getBag($bag)->first($dotName) }}</p>
    @elseif ($hint)
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
