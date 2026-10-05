@props(['name', 'label' => null, 'options' => [], 'value' => null, 'hint' => null, 'required' => false, 'placeholder' => null])
@php
    $id = $attributes->get('id', $name);
    $selected = (string) old($name, $value);
    $hasError = $errors->has($name);
@endphp
<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}@if ($required)<span class="text-rose-500"> *</span>@endif</label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}" @required($required) {{ $attributes->except('id')->class(['form-control pr-8', 'is-invalid' => $hasError]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($hasError)
        <p class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
