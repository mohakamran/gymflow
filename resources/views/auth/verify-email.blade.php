<x-layouts.guest title="Verify your email" heading="Check your inbox" subheading="We sent a verification link to {{ auth()->user()->email }}. Click it to activate your workspace.">
    @if (session('status') === 'verification-link-sent')
        <x-alert type="success" class="mb-6">A fresh verification link has been sent.</x-alert>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button icon="mail">Resend email</x-button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-button variant="ghost">Sign out</x-button>
        </form>
    </div>

    @if (app()->isLocal() && config('mail.default') === 'log')
        <p class="mt-8 text-xs text-zinc-500 dark:text-zinc-400">Local development: mail is written to <code>storage/logs/laravel.log</code>. Copy the verification link from there.</p>
    @endif
</x-layouts.guest>
