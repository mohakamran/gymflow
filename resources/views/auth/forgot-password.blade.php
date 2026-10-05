<x-layouts.guest title="Reset password" heading="Forgot your password?" subheading="Enter your email and we'll send you a secure link to choose a new one.">
    @if (session('status'))
        <x-alert type="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <x-input name="email" type="email" label="Email address" autocomplete="email" required autofocus />
        <x-button class="w-full" size="lg">Email reset link</x-button>
    </form>

    <p class="mt-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">Back to sign in</a>
    </p>
</x-layouts.guest>
