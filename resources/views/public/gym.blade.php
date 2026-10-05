@php
    $brand = preg_match('/^#[0-9a-fA-F]{6}$/', $tenant->primary_color) ? $tenant->primary_color : '#4f46e5';
    $about = $tenant->setting('profile.about');
    $address = collect([$tenant->address_line, $tenant->city, $tenant->state, $tenant->postal_code, $tenant->country])->filter()->join(', ');
    $description = $about ? \Illuminate\Support\Str::limit($about, 155) : $tenant->name.' — memberships, classes and personal training'.($tenant->city ? ' in '.$tenant->city : '').'.';
    $url = route('public.gym', $tenant->slug);
    $dayNames = ['monday' => 'Mo', 'tuesday' => 'Tu', 'wednesday' => 'We', 'thursday' => 'Th', 'friday' => 'Fr', 'saturday' => 'Sa', 'sunday' => 'Su'];
    $schema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'ExerciseGym',
        'name' => $tenant->name,
        'description' => $description,
        'url' => $url,
        'telephone' => $tenant->phone,
        'email' => $tenant->email,
        'image' => $tenant->logo_url,
        'address' => $address ? array_filter(['@type' => 'PostalAddress', 'streetAddress' => $tenant->address_line, 'addressLocality' => $tenant->city, 'addressRegion' => $tenant->state, 'postalCode' => $tenant->postal_code, 'addressCountry' => $tenant->country]) : null,
        'openingHours' => collect($hours)->reject(fn ($s) => $s['closed'])->map(fn ($s, $d) => $dayNames[$d].' '.$s['open'].'-'.$s['close'])->values()->all(),
        'priceRange' => $plans->isNotEmpty() ? money($plans->min(fn ($p) => $p->effectivePrice()), $tenant->currency).' – '.money($plans->max(fn ($p) => $p->effectivePrice()), $tenant->currency) : null,
    ]);
@endphp
<!DOCTYPE html>
<html lang="{{ $tenant->locale }}" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => $tenant->city ? 'Gym in '.$tenant->city : 'Gym', 'currentTenant' => $tenant])
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $url }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $tenant->name }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $url }}">
    @if ($tenant->logo_url)<meta property="og:image" content="{{ $tenant->logo_url }}">@endif
    <meta name="twitter:card" content="summary">
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</head>
<body class="font-sans">
<header class="sticky top-0 z-40 border-b border-zinc-200/70 bg-white/85 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-950/85">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
        <a href="#top" class="flex items-center gap-2.5">
            @if ($tenant->logo_url)<img src="{{ $tenant->logo_url }}" alt="" class="size-9 rounded-xl object-contain">@else<span class="grid size-9 place-items-center rounded-xl bg-brand-600 text-sm font-bold text-white">{{ $tenant->initials }}</span>@endif
            <span class="font-semibold">{{ $tenant->name }}</span>
        </a>
        <nav class="hidden items-center gap-6 text-sm font-medium text-zinc-600 md:flex dark:text-zinc-400">
            @if ($plans->isNotEmpty())<a href="#plans" class="hover:text-zinc-900 dark:hover:text-white">Memberships</a>@endif
            @if ($sessionsByDay->isNotEmpty())<a href="#schedule" class="hover:text-zinc-900 dark:hover:text-white">Classes</a>@endif
            @if ($trainers->isNotEmpty())<a href="#trainers" class="hover:text-zinc-900 dark:hover:text-white">Trainers</a>@endif
            <a href="#contact" class="hover:text-zinc-900 dark:hover:text-white">Contact</a>
        </nav>
        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="hidden text-sm font-medium text-zinc-600 sm:block dark:text-zinc-300">Member login</a>
            <x-button href="#contact" size="sm">Join now</x-button>
        </div>
    </div>
</header>

<main id="top">
    <section class="relative overflow-hidden bg-zinc-950 text-white">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,var(--color-brand-600),transparent_60%)] opacity-80"></div>
        <div class="relative mx-auto max-w-6xl px-4 py-24 sm:px-6 sm:py-32">
            <p class="text-sm font-semibold tracking-wider text-white/70 uppercase">{{ $tenant->city ?? 'Welcome' }}</p>
            <h1 class="mt-3 max-w-3xl text-4xl font-semibold tracking-tight text-balance sm:text-6xl">{{ $tenant->name }}</h1>
            <p class="mt-6 max-w-2xl text-lg text-white/80">{{ $about ?: 'Training, classes and coaching for every level. Come see what we\'re about.' }}</p>
            <div class="mt-10 flex flex-wrap gap-3">
                <a href="#contact" class="rounded-lg bg-white px-5 py-3 text-sm font-semibold text-zinc-900 hover:bg-zinc-100">Book a free visit</a>
                @if ($plans->isNotEmpty())<a href="#plans" class="rounded-lg px-5 py-3 text-sm font-semibold text-white ring-1 ring-white/30 hover:bg-white/10">See memberships</a>@endif
            </div>
        </div>
    </section>

    @if ($plans->isNotEmpty())
        <section id="plans" class="mx-auto max-w-6xl scroll-mt-16 px-4 py-20 sm:px-6">
            <h2 class="text-3xl font-semibold tracking-tight">Memberships</h2>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Simple plans, no surprises.</p>
            <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($plans as $plan)
                    <div class="card flex flex-col overflow-hidden">
                        <div class="h-1.5" style="background: {{ $plan->color }}"></div>
                        <div class="flex flex-1 flex-col p-6">
                            <h3 class="font-semibold">{{ $plan->name }}</h3>
                            <p class="text-sm text-zinc-500">{{ $plan->durationLabel() }}</p>
                            <p class="mt-4 text-4xl font-semibold tracking-tight">{{ money($plan->effectivePrice(), $tenant->currency) }}</p>
                            @if ((float) $plan->signup_fee > 0)<p class="text-xs text-zinc-500">+ {{ money($plan->signup_fee, $tenant->currency) }} joining fee</p>@endif
                            @if ($plan->description)<p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $plan->description }}</p>@endif
                            <ul class="mt-5 flex-1 space-y-2 text-sm">@foreach ($plan->features ?? [] as $feature)<li class="flex gap-2"><x-icon name="check" class="size-5 shrink-0 text-brand-600" />{{ $feature }}</li>@endforeach</ul>
                            <a href="#contact" onclick="document.getElementById('interest').value = @js($plan->name)" class="mt-6 rounded-lg bg-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-700">Get started</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($sessionsByDay->isNotEmpty())
        <section id="schedule" class="scroll-mt-16 border-y border-zinc-200 bg-white py-20 dark:border-zinc-800 dark:bg-zinc-900/40">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <h2 class="text-3xl font-semibold tracking-tight">This week's classes</h2>
                <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($sessionsByDay as $date => $sessions)
                        <div class="card p-5">
                            <p class="text-sm font-semibold">{{ \Carbon\Carbon::parse($date)->format('l, M j') }}</p>
                            <ul class="mt-3 space-y-3">
                                @foreach ($sessions as $session)
                                    <li class="flex items-start gap-3">
                                        <span class="mt-1 size-2.5 shrink-0 rounded-full" style="background: {{ $session->gymClass->color }}"></span>
                                        <div class="text-sm"><p class="font-medium">{{ $session->gymClass->name }}</p><p class="text-zinc-500">{{ $session->starts_at->copy()->setTimezone($tenant->timezone)->format('g:i A') }}@if ($session->trainer) · {{ $session->trainer->name }}@endif</p></div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($trainers->isNotEmpty())
        <section id="trainers" class="mx-auto max-w-6xl scroll-mt-16 px-4 py-20 sm:px-6">
            <h2 class="text-3xl font-semibold tracking-tight">Meet the coaches</h2>
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($trainers as $trainer)
                    <div class="text-center">
                        <x-avatar :user="$trainer" size="size-28" class="mx-auto text-2xl" />
                        <p class="mt-4 font-semibold">{{ $trainer->name }}</p>
                        <p class="text-sm text-brand-600">{{ $trainer->staffProfile->specialization ?? $trainer->staffProfile->job_title }}</p>
                        @if ($trainer->staffProfile->bio)<p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ \Illuminate\Support\Str::limit($trainer->staffProfile->bio, 140) }}</p>@endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section id="contact" class="scroll-mt-16 border-t border-zinc-200 bg-white py-20 dark:border-zinc-800 dark:bg-zinc-900/40">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-2">
            <div>
                <h2 class="text-3xl font-semibold tracking-tight">Visit us</h2>
                <dl class="mt-8 space-y-5 text-sm">
                    @if ($address)<div class="flex gap-3"><x-icon name="map-pin" class="size-5 text-brand-600" /><dd><a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($tenant->name.' '.$address) }}" target="_blank" rel="noopener" class="hover:underline">{{ $address }}</a></dd></div>@endif
                    @if ($tenant->phone)<div class="flex gap-3"><x-icon name="phone" class="size-5 text-brand-600" /><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $tenant->phone) }}" class="hover:underline">{{ $tenant->phone }}</a></dd></div>@endif
                    @if ($tenant->email)<div class="flex gap-3"><x-icon name="mail" class="size-5 text-brand-600" /><dd><a href="mailto:{{ $tenant->email }}" class="hover:underline">{{ $tenant->email }}</a></dd></div>@endif
                </dl>
                <h3 class="mt-10 text-sm font-semibold">Opening hours</h3>
                <dl class="mt-3 max-w-sm space-y-1.5 text-sm">
                    @foreach ($hours as $day => $slot)
                        <div class="flex justify-between"><dt class="capitalize text-zinc-500">{{ $day }}</dt><dd class="tabular-nums">{{ $slot['closed'] ? 'Closed' : $slot['open'].' – '.$slot['close'] }}</dd></div>
                    @endforeach
                </dl>
            </div>
            <div class="card p-6">
                @if (session('enquiry_sent'))
                    <x-empty-state icon="check-circle" title="Thanks — we'll be in touch!" description="Someone from our team will contact you shortly." />
                @else
                    <h3 class="text-lg font-semibold">Book a free visit</h3>
                    <p class="mt-1 text-sm text-zinc-500">Leave your details and we'll get back to you.</p>
                    <form method="POST" action="{{ route('public.enquire', $tenant->slug) }}" class="mt-6 space-y-4">
                        @csrf
                        <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                        <x-input name="name" label="Name" required />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-input name="email" type="email" label="Email" />
                            <x-input name="phone" type="tel" label="Phone" />
                        </div>
                        <x-input name="interest" id="interest" label="Interested in" placeholder="e.g. Monthly membership, yoga" />
                        <x-textarea name="message" label="Message" rows="3" />
                        <x-button class="w-full" size="lg">Send</x-button>
                    </form>
                @endif
            </div>
        </div>
    </section>
</main>
<footer class="py-8 text-center text-xs text-zinc-500">&copy; {{ date('Y') }} {{ $tenant->name }} · Powered by {{ config('app.name') }}</footer>
<x-toasts />
</body>
</html>
