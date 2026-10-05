@php $symbol = config('gym.currencies.'.tenant()->currency.'.0'); @endphp
<x-layouts.app title="New invoice">
    <x-page-header title="New invoice" description="For personal training, merchandise, day passes or anything else. Membership invoices are created automatically.">
        <x-slot:actions><x-button variant="secondary" icon="arrow-left" :href="route('invoices.index')">Back</x-button></x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('invoices.store') }}" class="grid gap-6 lg:grid-cols-[1fr_320px]"
          x-data="{
              items: @js(old('items', [['description' => '', 'quantity' => 1, 'unit_price' => '', 'discount' => 0, 'taxable' => true]])),
              taxEnabled: @js($taxEnabled), taxRate: @js($taxRate), symbol: @js($symbol),
              line(i) { const g = (Number(i.quantity) || 0) * (Number(i.unit_price) || 0); const net = Math.max(0, g - (Number(i.discount) || 0)); const tax = this.taxEnabled && i.taxable ? net * this.taxRate / 100 : 0; return { g, net, tax, total: net + tax } },
              get subtotal() { return this.items.reduce((s, i) => s + this.line(i).g, 0) },
              get discounts() { return this.items.reduce((s, i) => s + Math.min(this.line(i).g, Number(i.discount) || 0), 0) },
              get tax() { return this.items.reduce((s, i) => s + this.line(i).tax, 0) },
              get total() { return this.items.reduce((s, i) => s + this.line(i).total, 0) },
              fmt(v) { return this.symbol + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
          }">
        @csrf
        <div class="space-y-6">
            <x-card title="Bill to"><x-member-picker :member="$member" /></x-card>
            <x-card title="Line items" :padding="false">
                <div class="space-y-3 p-4">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid gap-3 rounded-xl border border-zinc-200 p-3 sm:grid-cols-12 dark:border-zinc-800">
                            <input class="form-control sm:col-span-5" :name="`items[${index}][description]`" x-model="item.description" placeholder="Description" aria-label="Description" required>
                            <input class="form-control sm:col-span-1" type="number" step="0.01" min="0.01" :name="`items[${index}][quantity]`" x-model="item.quantity" aria-label="Quantity" placeholder="Qty">
                            <input class="form-control sm:col-span-2" type="number" step="0.01" min="0" :name="`items[${index}][unit_price]`" x-model="item.unit_price" aria-label="Unit price" placeholder="Price" required>
                            <input class="form-control sm:col-span-2" type="number" step="0.01" min="0" :name="`items[${index}][discount]`" x-model="item.discount" aria-label="Discount" placeholder="Discount">
                            <div class="flex items-center justify-between gap-2 sm:col-span-2">
                                <label class="flex items-center gap-1.5 text-xs" x-show="taxEnabled"><input type="hidden" :name="`items[${index}][taxable]`" :value="item.taxable ? 1 : 0"><input type="checkbox" class="form-checkbox" x-model="item.taxable"> Tax</label>
                                <span class="text-sm font-medium tabular-nums" x-text="fmt(line(item).total)"></span>
                                <button type="button" @click="items.length > 1 && items.splice(index, 1)" class="text-zinc-400 hover:text-rose-600" aria-label="Remove line"><x-icon name="trash" class="size-4" /></button>
                            </div>
                        </div>
                    </template>
                    @error('items')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    <x-button type="button" size="sm" variant="secondary" icon="plus" @click="items.push({ description: '', quantity: 1, unit_price: '', discount: 0, taxable: true })">Add line</x-button>
                </div>
            </x-card>
            <x-card title="Notes"><x-textarea name="notes" rows="2" placeholder="Shown on the invoice" /></x-card>
        </div>
        <div class="space-y-6">
            <x-card title="Dates">
                <div class="space-y-4">
                    <x-input name="issued_on" type="date" label="Issue date" :value="tenant_today()->toDateString()" required />
                    <x-input name="due_on" type="date" label="Due date" :value="tenant_today()->addDays(7)->toDateString()" required />
                </div>
            </x-card>
            <x-card title="Total">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-zinc-500">Subtotal</dt><dd class="tabular-nums" x-text="fmt(subtotal)"></dd></div>
                    <div class="flex justify-between" x-show="discounts > 0"><dt class="text-zinc-500">Discounts</dt><dd class="tabular-nums" x-text="'-' + fmt(discounts)"></dd></div>
                    <div class="flex justify-between" x-show="tax > 0"><dt class="text-zinc-500">{{ tenant()->setting('tax.label', 'Tax') }}</dt><dd class="tabular-nums" x-text="fmt(tax)"></dd></div>
                    <div class="flex justify-between border-t border-zinc-100 pt-2 text-base font-semibold dark:border-zinc-800"><dt>Total</dt><dd class="tabular-nums" x-text="fmt(total)"></dd></div>
                </dl>
                <x-slot:footer><x-button class="w-full">Create invoice</x-button></x-slot:footer>
            </x-card>
        </div>
    </form>
</x-layouts.app>
