@props(['title' => null])
@php
    $user = auth()->user();
    $tenant = $currentTenant ?? null;
    $navigation = \App\Support\Navigation::for($user);
    $brandName = $tenant?->name ?? config('app.name');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="h-full font-sans" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[70] focus:rounded-lg focus:bg-white focus:px-3 focus:py-2 focus:text-sm focus:shadow">Skip to content</a>

{{-- Mobile backdrop --}}
<div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-zinc-950/50 backdrop-blur-sm lg:hidden"></div>

{{-- Sidebar --}}
<aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-zinc-200 bg-white transition-transform duration-200 lg:translate-x-0 dark:border-zinc-800 dark:bg-zinc-900"
       :class="sidebarOpen && 'translate-x-0'">
    <div class="flex h-16 shrink-0 items-center gap-3 px-5">
        @if ($tenant?->logo_url)
            <img src="{{ $tenant->logo_url }}" alt="{{ $tenant->name }}" class="size-9 rounded-xl object-contain">
        @else
            <span class="grid size-9 place-items-center rounded-xl bg-brand-600 text-sm font-bold text-white shadow-sm shadow-brand-600/30">{{ $tenant?->initials ?? 'GF' }}</span>
        @endif
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $brandName }}</p>
            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $tenant ? $tenant->subscription_plan->label().' plan' : 'Platform console' }}</p>
        </div>
        <button type="button" @click="sidebarOpen = false" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 lg:hidden dark:hover:bg-zinc-800" aria-label="Close menu">
            <x-icon name="x" />
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="Main">
        @foreach ($navigation as $section)
            <div>
                @if ($section['heading'])
                    <p class="px-3 pb-1.5 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500">{{ $section['heading'] }}</p>
                @endif
                <ul class="space-y-0.5">
                    @foreach ($section['items'] as $item)
                        @php $active = request()->routeIs($item['active']); @endphp
                        <li>
                            <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                               @class([
                                   'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                   'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $active,
                                   'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' => ! $active,
                               ])>
                                <x-icon :name="$item['icon']" @class(['size-5', 'text-brand-600 dark:text-brand-300' => $active, 'text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300' => ! $active]) />
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    @if ($tenant?->isOnTrial())
        <div class="m-3 rounded-xl bg-gradient-to-br from-brand-600 to-brand-800 p-4 text-white">
            <p class="text-sm font-semibold">Free trial</p>
            <p class="mt-0.5 text-xs text-white/80">{{ (int) ceil(now()->diffInDays($tenant->trial_ends_at)) }} days left on your {{ $tenant->subscription_plan->label() }} trial.</p>
        </div>
    @endif
</aside>

<div class="lg:pl-72">
    {{-- Top bar --}}
    <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-zinc-200 bg-white/80 px-4 backdrop-blur-md sm:px-6 lg:px-8 dark:border-zinc-800 dark:bg-zinc-950/80">
        <button type="button" @click="sidebarOpen = true" class="-ml-1 rounded-lg p-2 text-zinc-600 hover:bg-zinc-100 lg:hidden dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="Open menu">
            <x-icon name="menu" />
        </button>
        <div class="min-w-0 flex-1">
            @isset($breadcrumb)
                <div class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $breadcrumb }}</div>
            @endisset
        </div>

        @if ($tenant && \Illuminate\Support\Facades\Route::has('attendance.index') && $user->can(\App\Enums\Permission::AttendanceManage))
            <x-button :href="route('attendance.index')" size="sm" icon="qr" class="hidden sm:inline-flex">Check-in</x-button>
        @endif

        <x-notification-bell />

        <button type="button" @click="$store.theme.toggle()" class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white" aria-label="Toggle dark mode">
            <x-icon name="moon" class="size-5 dark:hidden" />
            <x-icon name="sun" class="hidden size-5 dark:block" />
        </button>

        <div x-data="{ open: false }" class="relative" @click.outside="open = false">
            <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-full p-0.5 pr-2 hover:bg-zinc-100 dark:hover:bg-zinc-800" :aria-expanded="open.toString()" aria-haspopup="menu">
                <x-avatar :user="$user" size="size-8" />
                <span class="hidden text-sm font-medium text-zinc-700 sm:block dark:text-zinc-200">{{ $user->name }}</span>
                <x-icon name="chevron-down" class="hidden size-4 text-zinc-400 sm:block" />
            </button>
            <div x-show="open" x-cloak x-transition.origin.top.right role="menu"
                 class="absolute right-0 mt-2 w-60 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg shadow-zinc-900/10 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                    <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $user->name }}</p>
                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                    <x-badge color="brand" class="mt-2">{{ $user->primaryRole()?->label() }}</x-badge>
                </div>
                <div class="p-1.5">
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" role="menuitem">
                        <x-icon name="user" class="size-4" /> Your profile
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" role="menuitem">
                            <x-icon name="logout" class="size-4" /> Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main id="main" class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        {{ $slot }}
    </main>
</div>

<x-toasts />
</body>
</html>
