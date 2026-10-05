<!DOCTYPE html>
<html lang="en" class="h-full dark">
<head>
    @include('partials.head', ['title' => 'Check-in kiosk'])
</head>
<body class="h-full bg-zinc-950 font-sans text-white">
<div x-data="{
        code: '', busy: false, result: null, error: null, timer: null,
        async submit() {
            if (!this.code.trim() || this.busy) return;
            this.busy = true; this.error = null; this.result = null;
            try {
                const response = await fetch(@js(route('attendance.store')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ code: this.code.trim(), toggle: true, method: 'kiosk' }),
                });
                const data = await response.json();
                response.ok ? this.result = data : this.error = data.message || 'Something went wrong.';
            } catch (e) { this.error = 'Network error. Please ask the front desk.'; }
            this.code = ''; this.busy = false;
            clearTimeout(this.timer); this.timer = setTimeout(() => { this.result = null; this.error = null; }, 5000);
            this.$refs.input.focus();
        }
     }" class="relative flex min-h-full flex-col items-center justify-center p-6" @click="$refs.input.focus()">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,var(--color-brand-700),transparent_60%)] opacity-60"></div>

    <div class="relative w-full max-w-lg text-center">
        @if ($currentTenant->logo_url)
            <img src="{{ $currentTenant->logo_url }}" alt="" class="mx-auto size-16 rounded-2xl bg-white object-contain p-1.5">
        @else
            <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-brand-600 text-xl font-bold">{{ $currentTenant->initials }}</span>
        @endif
        <h1 class="mt-6 text-3xl font-semibold tracking-tight">Welcome to {{ $currentTenant->name }}</h1>
        <p class="mt-2 text-white/60">Scan your member card to check in or out.</p>

        <form @submit.prevent="submit" class="mt-10">
            <input x-ref="input" x-model="code" autofocus autocomplete="off" aria-label="Member code"
                   class="w-full rounded-2xl border-0 bg-white/10 px-6 py-5 text-center font-mono text-2xl tracking-widest text-white ring-1 ring-white/20 placeholder:text-white/30 focus:ring-2 focus:ring-brand-400 focus:outline-none"
                   placeholder="Scan or enter code">
        </form>

        <div class="mt-8 min-h-40">
            <template x-if="busy"><p class="text-white/60">Checking…</p></template>
            <template x-if="result">
                <div class="rounded-3xl bg-white/10 p-6 ring-1 ring-white/15" x-transition>
                    <div class="mx-auto grid size-20 place-items-center overflow-hidden rounded-full bg-brand-600 text-2xl font-semibold">
                        <template x-if="result.member.photo"><img :src="result.member.photo" class="size-full object-cover" alt=""></template>
                        <template x-if="!result.member.photo"><span x-text="result.member.initials"></span></template>
                    </div>
                    <p class="mt-4 text-2xl font-semibold" x-text="(result.action === 'in' ? 'Welcome, ' : 'See you soon, ') + result.member.name.split(' ')[0] + '!'"></p>
                    <p class="mt-1 text-white/60" x-show="result.member.plan" x-text="result.member.plan + ' · ' + result.member.days_left + ' days left'"></p>
                </div>
            </template>
            <template x-if="error">
                <div class="rounded-3xl bg-rose-500/15 p-6 text-rose-100 ring-1 ring-rose-400/30" x-transition>
                    <p class="text-lg font-semibold" x-text="error"></p>
                    <p class="mt-1 text-sm text-rose-200/70">Please see the front desk.</p>
                </div>
            </template>
        </div>
    </div>
    <a href="{{ route('attendance.index') }}" class="absolute top-4 left-4 text-xs text-white/40 hover:text-white">Exit kiosk</a>
</div>
</body>
</html>
