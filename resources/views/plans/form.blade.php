@php $editing = $plan->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit plan' : 'New plan'">
    <x-page-header :title="$editing ? 'Edit '.$plan->name : 'New membership plan'">
        <x-slot:actions><x-button variant="secondary" icon="arrow-left" :href="route('plans.index')">Back</x-button></x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('plans.update', $plan) : route('plans.store') }}" class="grid gap-6 lg:grid-cols-[1fr_320px]">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="space-y-6">
            <x-card title="Plan">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2"><x-input name="name" label="Name" :value="$plan->name" placeholder="e.g. Monthly Unlimited" required autofocus /></div>
                    <div class="sm:col-span-2"><x-textarea name="description" label="Description" :value="$plan->description" rows="2" /></div>
                    <div class="grid grid-cols-2 gap-3">
                        <x-input name="duration_value" type="number" min="1" max="120" label="Duration" :value="$plan->duration_value" required />
                        <x-select name="duration_unit" label="Unit" :options="\App\Enums\DurationUnit::options()" :value="$plan->duration_unit?->value" required />
                    </div>
                    <div class="flex flex-wrap items-end gap-2 text-xs">
                        @foreach (['1 month' => [1, 'month'], '3 months' => [3, 'month'], '6 months' => [6, 'month'], '1 year' => [1, 'year']] as $label => [$value, $unit])
                            <button type="button" class="rounded-full px-2.5 py-1 font-medium ring-1 ring-zinc-300 hover:bg-zinc-50 dark:ring-zinc-700 dark:hover:bg-zinc-800"
                                    onclick="document.getElementById('duration_value').value={{ $value }};document.getElementById('duration_unit').value='{{ $unit }}'">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
            </x-card>

            <x-card title="Pricing" description="Amounts in {{ tenant()->currency }}.">
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-input name="price" type="number" step="0.01" min="0" label="Price" :value="$plan->price" required />
                    <x-input name="signup_fee" type="number" step="0.01" min="0" label="Joining fee" :value="$plan->signup_fee" hint="Charged on a member's first membership only." />
                    <x-input name="discount_percent" type="number" step="0.01" min="0" max="100" label="Discount %" :value="$plan->discount_percent" hint="Promotional price." />
                    <div class="sm:col-span-3"><x-toggle name="is_taxable" label="Taxable" :checked="$plan->is_taxable" description="Apply your gym's tax rate on invoices for this plan." /></div>
                </div>
            </x-card>

            <x-card title="Access & features">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="class_limit_per_week" type="number" min="1" label="Class limit per week" :value="$plan->class_limit_per_week" hint="Leave empty for unlimited." />
                    <x-input name="access_hours" label="Access restrictions" :value="$plan->access_hours" placeholder="e.g. Off-peak: 10am–4pm" />
                    <div class="sm:col-span-2"><x-textarea name="features" label="Features" :value="implode(PHP_EOL, old('features') ? [] : ($plan->features ?? []))" rows="4" hint="One per line — shown on your public page." /></div>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Visibility">
                <div class="space-y-5">
                    <x-toggle name="is_active" label="On sale" :checked="$plan->is_active" description="Retired plans can't be sold." />
                    <x-toggle name="is_public" label="Show on public page" :checked="$plan->is_public" />
                    <x-input name="sort_order" type="number" min="0" label="Sort order" :value="$plan->sort_order ?? 0" />
                    <div class="space-y-1.5">
                        <label for="color" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Color</label>
                        <input id="color" type="color" name="color" value="{{ old('color', $plan->color) }}" class="h-10 w-20 cursor-pointer rounded-lg border border-zinc-300 bg-transparent dark:border-zinc-700">
                    </div>
                </div>
                <x-slot:footer><x-button>{{ $editing ? 'Save plan' : 'Create plan' }}</x-button></x-slot:footer>
            </x-card>
        </div>
    </form>
</x-layouts.app>
