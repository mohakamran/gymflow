<x-layouts.guest title="Sign in" heading="Welcome back" subheading="Sign in to manage your gym.">
    @if (session('status'))
        <x-alert type="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        <x-input name="email" type="email" label="Email address" autocomplete="email" required autofocus />

        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label for="password" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Password</label>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">Forgot password?</a>
            </div>
            <input id="password" name="password" type="password" autocomplete="current-password" required class="form-control">
        </div>

        <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
            <input type="checkbox" name="remember" value="1" class="form-checkbox"> Keep me signed in
        </label>

        <x-button class="w-full" size="lg" x-bind:disabled="submitting">
            <span x-show="!submitting">Sign in</span>
            <span x-show="submitting" x-cloak>Signing in…</span>
        </x-button>
    </form>

    <p class="mt-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
        New to {{ config('app.name') }}?
        <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">Create your gym workspace</a>
    </p>

    @if (config('gym.show_demo_accounts'))
        @php $password = config('gym.demo_password'); @endphp
        <section class="mt-8 overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="demo-accounts"
                 x-data="{ fill(email) { document.getElementById('email').value = email; document.getElementById('password').value = @js($password); document.getElementById('password').focus(); window.toast('Credentials filled in — press Sign in', 'info'); } }">
            <header class="flex items-center justify-between gap-3 border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                <div>
                    <h2 id="demo-accounts" class="text-sm font-semibold text-zinc-900 dark:text-white">Demo accounts</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Copy an email and the password, or click <span class="font-medium">Use</span>.</p>
                </div>
                <x-badge color="amber">Demo</x-badge>
            </header>

            <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach (config('gym.demo_accounts') as $account)
                    <li class="flex items-center gap-3 px-4 py-2.5">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ $account['role'] }}</p>
                            <p class="truncate font-mono text-sm text-zinc-900 select-all dark:text-zinc-100" title="{{ $account['hint'] }}">{{ $account['email'] }}</p>
                        </div>
                        <x-copy-button :text="$account['email']" :label="$account['role'].' email'" />
                        <button type="button" @click="fill(@js($account['email']))" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-600 ring-1 ring-brand-200 hover:bg-brand-50 dark:text-brand-300 dark:ring-brand-500/30 dark:hover:bg-brand-500/10">Use</button>
                    </li>
                @endforeach
            </ul>

            <footer class="flex items-center gap-3 border-t border-zinc-100 bg-zinc-50/70 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/60">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Password (all accounts)</p>
                    <p class="font-mono text-sm text-zinc-900 select-all dark:text-zinc-100">{{ $password }}</p>
                </div>
                <x-copy-button :text="$password" label="Password" />
            </footer>
        </section>
    @endif
</x-layouts.guest>
