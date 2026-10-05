<x-layouts.guest title="Choose a new password" heading="Choose a new password">
    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-input name="email" type="email" label="Email address" :value="$request->email" autocomplete="email" required />
        <x-input name="password" type="password" label="New password" autocomplete="new-password" required autofocus />
        <x-input name="password_confirmation" type="password" label="Confirm new password" autocomplete="new-password" required />
        <x-button class="w-full" size="lg">Reset password</x-button>
    </form>
</x-layouts.guest>
