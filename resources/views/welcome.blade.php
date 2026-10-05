@php
    $features = [
        ['users', 'Members & memberships', 'Profiles, plans, renewals, freezes and expiry tracking with automatic reminders.'],
        ['qr', 'Fast check-ins', 'Front-desk check-in and check-out today, QR-code check-in when you are ready.'],
        ['card', 'Payments & invoices', 'Partial payments, discounts and tax, with branded PDF invoices sent by email.'],
        ['calendar', 'Classes & trainers', 'Recurring class schedules, capacity limits, trainer assignments and workout plans.'],
        ['chart', 'Reports that matter', 'Revenue, profit, attendance and growth, exportable to PDF and CSV.'],
        ['shield', 'Secure by design', 'Isolated workspace per gym, role-based access and a full audit trail.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    @include('partials.head', ['title' => 'Gym management software'])
    <meta name="description" content="{{ config('app.name') }} is all-in-one gym management software: members, memberships, attendance, billing, classes, staff and reports in one branded workspace.">
</head>
<body class="font-sans">
<header class="sticky top-0 z-40 border-b border-zinc-200/70 bg-white/80 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-950/80">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <a href="{{ route('welcome') }}" class="flex items-center gap-2.5">
            <span class="grid size-9 place-items-center rounded-xl bg-brand-600 text-white shadow-md shadow-brand-600/30"><x-icon name="bolt" class="size-5" /></span>
            <span class="font-semibold tracking-tight">{{ config('app.name') }}</span>
        </a>
        <nav class="flex items-center gap-1 sm:gap-2">
            <a href="#features" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 hover:text-zinc-900 sm:block dark:text-zinc-400 dark:hover:text-white">Features</a>
            <a href="#pricing" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 hover:text-zinc-900 sm:block dark:text-zinc-400 dark:hover:text-white">Pricing</a>
            <button type="button" x-data @click="$store.theme.toggle()" class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Toggle dark mode">
                <x-icon name="moon" class="size-5 dark:hidden" /><x-icon name="sun" class="hidden size-5 dark:block" />
            </button>
            @auth
                <x-button :href="route('home')" size="sm">Open app</x-button>
            @else
                <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">Sign in</a>
                <x-button :href="route('register')" size="sm">Start free trial</x-button>
            @endauth
        </nav>
    </div>
</header>

<main>
    <section class="relative overflow-hidden">
        <div class="absolute inset-x-0 top-0 -z-10 h-[520px] bg-[radial-gradient(ellipse_at_top,var(--color-brand-100),transparent_65%)] dark:bg-[radial-gradient(ellipse_at_top,color-mix(in_oklab,var(--brand)_25%,transparent),transparent_65%)]"></div>
        <div class="mx-auto max-w-6xl px-4 pt-20 pb-16 text-center sm:px-6 sm:pt-28">
            <x-badge color="brand" class="px-3 py-1"><x-icon name="sparkles" class="size-3.5" /> 14-day free trial · no card required</x-badge>
            <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-semibold tracking-tight text-balance sm:text-6xl">Run your whole gym from one beautiful workspace</h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-pretty text-zinc-600 dark:text-zinc-400">Members, memberships, check-ins, payments, classes, staff and reports, branded with your logo and colors.</p>
            <div class="mt-10 flex flex-wrap justify-center gap-3">
                <x-button :href="route('register')" size="lg" icon="arrow-right">Create your gym</x-button>
                <x-button :href="route('login')" variant="secondary" size="lg">Sign in</x-button>
            </div>
        </div>
    </section>

    <section id="features" class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <div class="max-w-2xl">
            <h2 class="text-3xl font-semibold tracking-tight">Everything the front desk and the back office need</h2>
            <p class="mt-3 text-zinc-600 dark:text-zinc-400">Built for owners, receptionists, trainers and members, each with exactly the access they need.</p>
        </div>
        <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($features as [$icon, $title, $text])
                <div class="card p-6">
                    <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300"><x-icon :name="$icon" /></span>
                    <h3 class="mt-4 font-semibold">{{ $title }}</h3>
                    <p class="mt-1.5 text-sm text-zinc-600 dark:text-zinc-400">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section id="pricing" class="border-t border-zinc-200 bg-white py-20 dark:border-zinc-800 dark:bg-zinc-900/40">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-semibold tracking-tight">Simple pricing that grows with you</h2>
                <p class="mt-3 text-zinc-600 dark:text-zinc-400">Start on any plan free for 14 days.</p>
            </div>
            <div class="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach (\App\Enums\SubscriptionPlan::cases() as $plan)
                    @php $featured = $plan === \App\Enums\SubscriptionPlan::Professional; @endphp
                    <div @class(['card flex flex-col p-6', 'ring-2 ring-brand-600' => $featured])>
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold">{{ $plan->label() }}</h3>
                            @if ($featured)<x-badge color="brand">Most popular</x-badge>@endif
                        </div>
                        <p class="mt-4">
                            @if ($plan->monthlyPrice())
                                <span class="text-4xl font-semibold tracking-tight">${{ $plan->monthlyPrice() }}</span><span class="text-sm text-zinc-500">/month</span>
                            @else
                                <span class="text-4xl font-semibold tracking-tight">Custom</span>
                            @endif
                        </p>
                        <ul class="mt-6 flex-1 space-y-2.5 text-sm">
                            @foreach ($plan->highlights() as $line)
                                <li class="flex gap-2"><x-icon name="check" class="size-5 shrink-0 text-brand-600" />{{ $line }}</li>
                            @endforeach
                        </ul>
                        <x-button :href="route('register')" :variant="$featured ? 'primary' : 'secondary'" class="mt-8 w-full">{{ $plan->monthlyPrice() ? 'Start free trial' : 'Contact sales' }}</x-button>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</main>

<footer class="border-t border-zinc-200 py-10 dark:border-zinc-800">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 text-sm text-zinc-500 sm:px-6">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
        <p>Gym management software</p>
    </div>
</footer>
</body>
</html>
