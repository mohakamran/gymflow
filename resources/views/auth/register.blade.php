@php
    $currencies = collect(config('gym.currencies'))->mapWithKeys(fn ($c, $code) => [$code => "$code — {$c[1]}"])->all();
    $timezones = array_combine(\DateTimeZone::listIdentifiers(), \DateTimeZone::listIdentifiers());
@endphp
<x-layouts.guest title="Create your gym" heading="Start your 14-day free trial" subheading="Set up your gym workspace in under a minute. No card required." :wide="true">
    <form method="POST" action="{{ route('register') }}" class="space-y-6" x-data="{ submitting: false }" @submit="submitting = true"
          x-init="if (!$refs.tz.dataset.touched) { const tz = Intl.DateTimeFormat().resolvedOptions().timeZone; if ([...$refs.tz.options].some(o => o.value === tz) && !@js((bool) old('timezone'))) $refs.tz.value = tz; }">
        @csrf
        <fieldset class="space-y-4">
            <legend class="text-xs font-semibold tracking-wider text-zinc-400 uppercase">Your gym</legend>
            <x-input name="gym_name" label="Gym name" placeholder="e.g. Iron Peak Fitness" required autofocus />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-select name="currency" label="Currency" :options="$currencies" value="USD" required />
                <x-select name="timezone" label="Timezone" :options="$timezones" value="UTC" x-ref="tz" @change="$el.dataset.touched = 1" required />
            </div>
        </fieldset>

        <fieldset class="space-y-4">
            <legend class="text-xs font-semibold tracking-wider text-zinc-400 uppercase">Owner account</legend>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input name="name" label="Your full name" autocomplete="name" required />
                <x-input name="phone" label="Phone" type="tel" autocomplete="tel" />
            </div>
            <x-input name="email" type="email" label="Work email" autocomplete="email" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input name="password" type="password" label="Password" autocomplete="new-password" required />
                <x-input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" required />
            </div>
        </fieldset>

        <div>
            <label class="flex items-start gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                <input type="checkbox" name="terms" value="1" class="form-checkbox mt-0.5" @checked(old('terms'))>
                <span>I agree to the terms of service and privacy policy.</span>
            </label>
            @error('terms')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        </div>

        <x-button class="w-full" size="lg" x-bind:disabled="submitting">
            <span x-show="!submitting">Create gym workspace</span>
            <span x-show="submitting" x-cloak>Setting things up…</span>
        </x-button>
    </form>

    <p class="mt-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
        Already have an account? <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">Sign in</a>
    </p>
</x-layouts.guest>
