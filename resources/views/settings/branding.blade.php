<x-layouts.app title="Branding">
    <x-page-header title="Settings" description="Manage how your gym appears to members and on invoices." />
    @include('settings._nav')

    <form method="POST" action="{{ route('settings.branding.update') }}" enctype="multipart/form-data"
          x-data="{ color: @js(old('primary_color', $tenant->primary_color)) }"
          x-effect="if (/^#[0-9a-fA-F]{6}$/.test(color)) document.documentElement.style.setProperty('--brand', color)">
        @csrf @method('PUT')
        <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <x-card title="Brand identity" description="Your logo and color are used across the dashboard, member portal, invoices and emails.">
                <div class="space-y-8">
                    @foreach (['logo' => ['Logo', 'Square PNG, JPG or WebP, up to 2 MB.', $tenant->logo_url], 'favicon' => ['Favicon', 'Square image, at least 64×64, up to 512 KB.', $tenant->favicon_url]] as $field => [$label, $hint, $current])
                        <div x-data="imagePreview(@js($current))" class="flex flex-wrap items-center gap-5">
                            <div class="grid size-20 place-items-center overflow-hidden rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                                <template x-if="preview"><img :src="preview" alt="" class="size-full object-contain p-1.5"></template>
                                <template x-if="!preview"><x-icon name="photo" class="size-7 text-zinc-400" /></template>
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $label }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-zinc-800 ring-1 ring-zinc-300 ring-inset hover:bg-zinc-50 dark:bg-zinc-900 dark:text-zinc-100 dark:ring-zinc-700 dark:hover:bg-zinc-800">
                                        Upload
                                        <input x-ref="input" type="file" name="{{ $field }}" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pick">
                                    </label>
                                    <button type="button" x-show="preview" @click="clear" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10">Remove</button>
                                </div>
                                <input type="hidden" name="remove_{{ $field }}" :value="removed ? 1 : 0" value="0">
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
                                @error($field)<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @endforeach

                    <div>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Brand color</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @foreach (config('gym.brand_presets') as $preset)
                                <button type="button" @click="color = @js($preset)" class="size-8 rounded-full ring-offset-2 transition hover:scale-110 dark:ring-offset-zinc-900"
                                        :class="color.toLowerCase() === @js($preset) && 'ring-2 ring-zinc-900 dark:ring-white'" style="background: {{ $preset }}" aria-label="Use {{ $preset }}"></button>
                            @endforeach
                            <div class="ml-1 flex items-center gap-2">
                                <input type="color" x-model="color" class="h-8 w-10 cursor-pointer rounded-md border border-zinc-300 bg-transparent dark:border-zinc-700" aria-label="Custom color">
                                <input type="text" name="primary_color" x-model="color" maxlength="7" class="form-control w-28 font-mono uppercase">
                            </div>
                        </div>
                        @error('primary_color')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <x-slot:footer><x-button>Save branding</x-button></x-slot:footer>
            </x-card>

            <div class="space-y-3">
                <p class="text-xs font-semibold tracking-wider text-zinc-400 uppercase">Live preview</p>
                <div class="card overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-zinc-100 p-4 dark:border-zinc-800">
                        <span class="grid size-9 place-items-center rounded-xl text-sm font-bold text-white" :style="`background:${color}`">{{ $tenant->initials }}</span>
                        <div><p class="text-sm font-semibold">{{ $tenant->name }}</p><p class="text-xs text-zinc-500">Member portal</p></div>
                    </div>
                    <div class="space-y-3 p-4">
                        <div class="rounded-lg px-3 py-2 text-sm font-medium" :style="`background:color-mix(in oklab, ${color} 10%, transparent); color:${color}`">Dashboard</div>
                        <div class="h-2 rounded-full bg-zinc-100 dark:bg-zinc-800"><div class="h-full w-2/3 rounded-full" :style="`background:${color}`"></div></div>
                        <button type="button" class="w-full rounded-lg py-2 text-sm font-semibold text-white" :style="`background:${color}`">Renew membership</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-layouts.app>
