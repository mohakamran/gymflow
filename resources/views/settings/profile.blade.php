<x-layouts.app title="Gym profile">
    <x-page-header title="Settings" description="Manage how your gym appears to members and on invoices." />
    @include('settings._nav')

    <form method="POST" action="{{ route('settings.profile.update') }}">
        @csrf @method('PUT')
        <x-card title="Gym profile" description="Contact details appear on invoices, emails and your public page.">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><x-input name="name" label="Gym name" :value="$tenant->name" required /></div>
                <x-input name="email" type="email" label="Contact email" :value="$tenant->email" />
                <x-input name="phone" type="tel" label="Phone" :value="$tenant->phone" />
                <div class="sm:col-span-2"><x-input name="website" type="url" label="Website" :value="$tenant->website" placeholder="https://" /></div>
                <div class="sm:col-span-2"><x-input name="address_line" label="Street address" :value="$tenant->address_line" /></div>
                <x-input name="city" label="City" :value="$tenant->city" />
                <x-input name="state" label="State / region" :value="$tenant->state" />
                <x-input name="postal_code" label="Postal code" :value="$tenant->postal_code" />
                <x-input name="country" label="Country code" :value="$tenant->country" maxlength="2" placeholder="US" hint="Two-letter ISO code, e.g. US, GB, PK, AE." />
                <div class="sm:col-span-2"><x-textarea name="about" label="About your gym" :value="$tenant->setting('profile.about')" hint="Shown on your public gym page." /></div>
                <div class="sm:col-span-2 rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <x-toggle name="public_profile_enabled" label="Public gym page" :checked="$tenant->public_profile_enabled" description="Publish an SEO-friendly page with your plans, classes and contact details." />
                </div>
            </div>
            <x-slot:footer><x-button>Save changes</x-button></x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
