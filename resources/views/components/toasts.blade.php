@php
    $flash = session('toast');
    $initial = [];
    if (is_array($flash) && isset($flash['message'])) {
        $initial[] = ['message' => (string) $flash['message'], 'type' => in_array($flash['type'] ?? '', ['success', 'error', 'info', 'warning'], true) ? $flash['type'] : 'success'];
    }
@endphp
<div x-data x-init="@js($initial).forEach(t => $store.toasts.push(t.message, t.type))"
     class="pointer-events-none fixed inset-x-0 bottom-0 z-[60] flex flex-col items-center gap-2 p-4 sm:items-end sm:p-6" aria-live="polite">
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div x-show="toast.visible" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-lg shadow-zinc-900/5 dark:border-zinc-800 dark:bg-zinc-900">
            <span class="mt-0.5 size-2.5 shrink-0 rounded-full"
                  :class="{ 'bg-emerald-500': toast.type === 'success', 'bg-rose-500': toast.type === 'error', 'bg-amber-500': toast.type === 'warning', 'bg-sky-500': toast.type === 'info' }"></span>
            <p class="flex-1 text-sm font-medium text-zinc-800 dark:text-zinc-100" x-text="toast.message"></p>
            <button type="button" @click="$store.toasts.dismiss(toast.id)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" aria-label="Dismiss">
                <x-icon name="x" class="size-4" />
            </button>
        </div>
    </template>
</div>
