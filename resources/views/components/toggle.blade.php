@props(['name', 'label', 'checked' => false, 'description' => null])
@php $isOn = (bool) old($name, $checked); @endphp
<label x-data="{ on: @js($isOn) }" class="flex cursor-pointer items-start justify-between gap-4">
    <span>
        <span class="block text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $label }}</span>
        @if ($description)<span class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</span>@endif
    </span>
    <input type="hidden" name="{{ $name }}" :value="on ? 1 : 0" value="{{ $isOn ? 1 : 0 }}">
    <button type="button" role="switch" :aria-checked="on.toString()" @click="on = !on" {{ $attributes }}
            class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 rounded-full transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500"
            :class="on ? 'bg-brand-600' : 'bg-zinc-200 dark:bg-zinc-700'">
        <span class="pointer-events-none inline-block size-5 translate-y-0.5 rounded-full bg-white shadow ring-0 transition"
              :class="on ? 'translate-x-5.5' : 'translate-x-0.5'"></span>
    </button>
</label>
