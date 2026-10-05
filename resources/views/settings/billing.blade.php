<x-layouts.app title="Plan & billing">
    <x-page-header title="Settings" description="Manage how your gym appears to members and on invoices." />
    @include('settings._nav')

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Current plan" class="lg:col-span-1">
            <p class="text-2xl font-semibold">{{ $tenant->subscription_plan->label() }}</p>
            <p class="text-sm text-zinc-500">
                @if ($tenant->isOnTrial()) Free trial · ends {{ $tenant->trial_ends_at->toFormattedDateString() }}
                @elseif ($tenant->subscription_plan->monthlyPrice()) ${{ $tenant->subscription_plan->monthlyPrice() }}/month
                @else Custom pricing @endif
            </p>
            <div class="mt-6 space-y-4">
                @foreach (['members' => 'Active members', 'staff' => 'Team accounts'] as $key => $label)
                    @php $u = $usage[$key]; $pct = $u['limit'] ? min(100, round($u['used'] / $u['limit'] * 100)) : 0; @endphp
                    <div>
                        <div class="flex justify-between text-sm"><span>{{ $label }}</span><span class="tabular-nums">{{ $u['used'] }} / {{ $u['limit'] ?? '∞' }}</span></div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"><div @class(['h-full rounded-full', 'bg-brand-600' => $pct < 90, 'bg-rose-500' => $pct >= 90]) style="width: {{ $u['limit'] ? $pct : 4 }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <div class="space-y-6 lg:col-span-2">
            @if ($pending)
                <x-alert type="info">Your request to switch to <strong>{{ $pending->requested_plan->label() }}</strong> is pending review (sent {{ $pending->created_at->diffForHumans() }}).</x-alert>
            @endif
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($plans as $plan)
                    @php $current = $plan === $tenant->subscription_plan; @endphp
                    <div @class(['card flex flex-col p-5', 'ring-2 ring-brand-600' => $current])>
                        <div class="flex items-center justify-between"><h3 class="font-semibold">{{ $plan->label() }}</h3>@if ($current)<x-badge color="brand">Current</x-badge>@endif</div>
                        <p class="mt-2 text-2xl font-semibold">{{ $plan->monthlyPrice() ? '$'.$plan->monthlyPrice() : 'Custom' }}<span class="text-sm font-normal text-zinc-500">{{ $plan->monthlyPrice() ? '/mo' : '' }}</span></p>
                        <ul class="mt-3 flex-1 space-y-1.5 text-sm text-zinc-600 dark:text-zinc-400">@foreach ($plan->highlights() as $line)<li class="flex gap-2"><x-icon name="check" class="size-4 shrink-0 text-brand-600" />{{ $line }}</li>@endforeach</ul>
                        @unless ($current)
                            <form method="POST" action="{{ route('settings.billing.request') }}" class="mt-4">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $plan->value }}">
                                <x-button class="w-full" variant="secondary">{{ ($plan->monthlyPrice() ?? 999) > ($tenant->subscription_plan->monthlyPrice() ?? 999) ? 'Request upgrade' : 'Request change' }}</x-button>
                            </form>
                        @endunless
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-zinc-500">Plan changes are confirmed by our team. Online card billing for subscriptions will be available soon.</p>
        </div>
    </div>
</x-layouts.app>
