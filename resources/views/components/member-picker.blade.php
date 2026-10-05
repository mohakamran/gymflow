@props(['name' => 'member_id', 'member' => null, 'label' => 'Member', 'required' => true, 'autofocus' => false])
@php
    $initial = $member ? ['id' => $member->id, 'name' => $member->full_name, 'code' => $member->member_code, 'initials' => $member->initials, 'photo' => $member->photo_url] : null;
@endphp
<div x-data="memberPicker(@js(route('members.lookup')), @js($initial))" class="relative space-y-1.5" @click.outside="open = false" {{ $attributes }}>
    @if ($label)<label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}@if ($required)<span class="text-rose-500"> *</span>@endif</label>@endif
    <input type="hidden" name="{{ $name }}" :value="selected?.id ?? ''">
    <template x-if="selected">
        <div class="flex items-center gap-3 rounded-lg bg-zinc-50 px-3 py-2 ring-1 ring-zinc-200 dark:bg-zinc-800/60 dark:ring-zinc-700">
            <span class="grid size-8 place-items-center overflow-hidden rounded-full bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-brand-500/20 dark:text-brand-200">
                <template x-if="selected.photo"><img :src="selected.photo" alt="" class="size-full object-cover"></template>
                <template x-if="!selected.photo"><span x-text="selected.initials"></span></template>
            </span>
            <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium" x-text="selected.name"></p><p class="text-xs text-zinc-500" x-text="selected.code"></p></div>
            <button type="button" @click="selected = null; $nextTick(() => $refs.search.focus()); $dispatch('member-cleared')" class="text-xs font-semibold text-brand-600 dark:text-brand-400">Change</button>
        </div>
    </template>
    <div x-show="!selected" class="relative">
        <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-zinc-400" />
        <input x-ref="search" type="search" x-model="query" @input="search" @focus="results.length && (open = true)" @keydown.escape="open = false"
               placeholder="Name, code, email or phone" autocomplete="off" class="form-control pl-9 @error($name) is-invalid @enderror" @if ($autofocus) autofocus @endif>
        <div x-show="open" x-cloak class="absolute z-30 mt-1 w-full overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            <template x-for="result in results" :key="result.id">
                <button type="button" @click="choose(result)" class="flex w-full items-center gap-3 px-3 py-2 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800">
                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-zinc-100 text-xs font-semibold dark:bg-zinc-800" x-text="result.initials"></span>
                    <span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium" x-text="result.name"></span><span class="block truncate text-xs text-zinc-500" x-text="result.code + ' · ' + (result.plan ?? result.status)"></span></span>
                </button>
            </template>
            <p x-show="!loading && results.length === 0" class="px-3 py-3 text-sm text-zinc-500">No members found.</p>
        </div>
    </div>
    @error($name)<p class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
</div>
