@props(['action', 'method' => 'POST', 'title' => 'Are you sure?', 'message' => null, 'confirm' => 'Confirm', 'variant' => 'danger', 'trigger' => 'Delete', 'triggerVariant' => 'ghost', 'icon' => null, 'size' => 'sm'])
{{-- A button that opens a confirmation dialog before submitting a form. Extra fields go in the slot. --}}
<div x-data="{ open: false }" class="inline-block">
    <x-button type="button" :variant="$triggerVariant" :size="$size" :icon="$icon" @click="open = true" {{ $attributes }}>{{ $trigger }}</x-button>
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[65] grid place-items-center bg-zinc-950/50 p-4 backdrop-blur-sm" @keydown.escape.window="open = false" @click.self="open = false" x-transition.opacity>
            <form method="POST" action="{{ $action }}" class="card w-full max-w-md p-6 text-left" x-transition>
                @csrf
                @if (strtoupper($method) !== 'POST') @method($method) @endif
                <div class="flex gap-4">
                    <span @class(['grid size-10 shrink-0 place-items-center rounded-full', 'bg-rose-100 text-rose-600 dark:bg-rose-500/15' => $variant === 'danger', 'bg-brand-100 text-brand-600 dark:bg-brand-500/15' => $variant !== 'danger'])>
                        <x-icon :name="$variant === 'danger' ? 'exclamation' : 'info'" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>
                        @if ($message)<p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $message }}</p>@endif
                        @if ($slot->isNotEmpty())<div class="mt-4 space-y-4">{{ $slot }}</div>@endif
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <x-button type="button" variant="secondary" @click="open = false">Cancel</x-button>
                    <x-button :variant="$variant">{{ $confirm }}</x-button>
                </div>
            </form>
        </div>
    </template>
</div>
