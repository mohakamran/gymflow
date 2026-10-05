@props(['title' => null, 'heading' => null, 'subheading' => null, 'wide' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="h-full font-sans">
<div class="flex min-h-full">
    <div class="flex flex-1 flex-col justify-center px-4 py-10 sm:px-6 lg:flex-none lg:px-16 xl:px-24">
        <div @class(['mx-auto w-full', 'max-w-sm' => ! $wide, 'max-w-xl' => $wide])>
            <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2.5">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-600 text-white shadow-md shadow-brand-600/30"><x-icon name="bolt" class="size-5" /></span>
                <span class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-white">{{ config('app.name') }}</span>
            </a>
            @if ($heading)
                <h1 class="mt-8 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $heading }}</h1>
            @endif
            @if ($subheading)
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $subheading }}</p>
            @endif
            <div class="mt-8">{{ $slot }}</div>
        </div>
    </div>
    <div class="relative hidden flex-1 overflow-hidden bg-zinc-950 lg:block">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,var(--color-brand-600),transparent_60%)] opacity-70"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_left,var(--color-brand-900),transparent_55%)]"></div>
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgb(255_255_255/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(255_255_255/0.04)_1px,transparent_1px)] bg-[size:48px_48px]"></div>
        <div class="relative flex h-full flex-col justify-end p-14 text-white">
            <div class="max-w-lg">
                <p class="text-sm font-semibold tracking-wide text-white/70 uppercase">Gym management, done properly</p>
                <p class="mt-3 text-3xl leading-tight font-semibold">Members, memberships, check-ins, billing and your team — in one branded workspace.</p>
                <p class="mt-4 text-sm text-white/70">Every gym gets its own isolated workspace, its own branding and its own access rules.</p>
            </div>
            <dl class="mt-12 grid max-w-lg grid-cols-3 gap-6 border-t border-white/10 pt-8">
                <div><dt class="text-xs text-white/60">Isolated</dt><dd class="mt-1 text-sm font-semibold">Per-gym data</dd></div>
                <div><dt class="text-xs text-white/60">Roles</dt><dd class="mt-1 text-sm font-semibold">5 access levels</dd></div>
                <div><dt class="text-xs text-white/60">Audit</dt><dd class="mt-1 text-sm font-semibold">Every change</dd></div>
            </dl>
        </div>
    </div>
</div>
<x-toasts />
</body>
</html>
