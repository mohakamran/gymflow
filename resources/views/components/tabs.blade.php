@props(['tabs', 'default' => null])
{{-- Client-side tabs synced with the URL hash. $tabs: [key => label]. Panels use x-show="tab === 'key'". --}}
<div x-data="{ tab: (location.hash.slice(1) in @js($tabs)) ? location.hash.slice(1) : @js($default ?? array_key_first($tabs)) }"
     x-init="$watch('tab', value => history.replaceState(null, '', '#' + value))" {{ $attributes }}>
    <div class="-mx-4 mb-6 overflow-x-auto border-b border-zinc-200 px-4 sm:mx-0 sm:px-0 dark:border-zinc-800">
        <nav class="flex min-w-max gap-6" role="tablist">
            @foreach ($tabs as $key => $label)
                <button type="button" role="tab" @click="tab = @js($key)" :aria-selected="(tab === @js($key)).toString()"
                        class="-mb-px border-b-2 px-0.5 py-3 text-sm font-medium transition"
                        :class="tab === @js($key) ? 'border-brand-600 text-brand-700 dark:text-brand-300' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'">{{ $label }}</button>
            @endforeach
        </nav>
    </div>
    {{ $slot }}
</div>
