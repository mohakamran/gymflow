@props(['name', 'label' => null, 'value' => null, 'hint' => null, 'rows' => 4])
@php $hasError = $errors->has($name); @endphp
<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</label>
    @endif
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" {{ $attributes->class(['form-control', 'is-invalid' => $hasError]) }}>{{ old($name, $value) }}</textarea>
    @if ($hasError)
        <p class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
