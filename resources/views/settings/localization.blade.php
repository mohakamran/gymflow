@php
    $currencies = collect(config('gym.currencies'))->mapWithKeys(fn ($c, $code) => [$code => "$code ({$c[0]}) — {$c[1]}"])->all();
@endphp
<x-layouts.app title="Regional, tax & invoices">
    <x-page-header title="Settings" description="Manage how your gym appears to members and on invoices." />
    @include('settings._nav')

    <form method="POST" action="{{ route('settings.localization.update') }}" class="space-y-6">
        @csrf @method('PUT')
        <x-card title="Regional" description="Used for prices, dates and reminders.">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-select name="currency" label="Currency" :options="$currencies" :value="$tenant->currency" required />
                <x-select name="timezone" label="Timezone" :options="array_combine($timezones, $timezones)" :value="$tenant->timezone" required />
                <x-select name="locale" label="Language" :options="config('gym.locales')" :value="$tenant->locale" required />
                <x-select name="date_format" label="Date format" :options="$dateFormats" :value="$tenant->setting('date_format', 'M j, Y')" required />
            </div>
        </x-card>

        <x-card title="Tax" description="Applied to membership sales and invoices.">
            <div class="space-y-5">
                <x-toggle name="tax_enabled" label="Charge tax" :checked="$tenant->setting('tax.enabled', false)" description="Adds tax to invoice totals." />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="tax_label" label="Tax label" :value="$tenant->setting('tax.label', 'Tax')" hint="e.g. VAT, GST, Sales tax" required />
                    <x-input name="tax_rate" type="number" step="0.01" min="0" max="100" label="Rate (%)" :value="$tenant->setting('tax.rate', 0)" required />
                </div>
            </div>
        </x-card>

        <x-card title="Invoices" description="Numbering and the note printed at the bottom of each invoice.">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-input name="invoice_prefix" label="Number prefix" :value="$tenant->setting('invoice.prefix', 'INV-')" hint="Letters, numbers, - and / only." required />
                <div class="sm:col-span-2"><x-textarea name="invoice_footer" label="Invoice footer" rows="3" :value="$tenant->setting('invoice.footer')" /></div>
            </div>
            <x-slot:footer><x-button>Save settings</x-button></x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
