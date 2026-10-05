@props(['reset' => null])
{{-- Filter row placed above a table. --}}
<form method="GET" {{ $attributes->class(['flex flex-wrap items-end gap-3 border-b border-zinc-100 p-4 dark:border-zinc-800']) }}>
    {{ $slot }}
    <div class="flex gap-2">
        <x-button size="md">Apply</x-button>
        @if ($reset && count(array_filter(request()->query())) > 0)
            <x-button variant="ghost" :href="$reset">Reset</x-button>
        @endif
    </div>
</form>
