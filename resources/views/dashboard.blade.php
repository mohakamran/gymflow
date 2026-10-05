<x-layouts.app title="Dashboard">
    @php
        $user = auth()->user();
        $hour = tenant_now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $setupDone = collect($setupSteps)->where('done', true)->count();
        $setupProgress = (int) round($setupDone / count($setupSteps) * 100);
        $P = \App\Enums\Permission::class;
    @endphp

    <x-page-header :title="$greeting.', '.str($user->name)->before(' ')" :description="tenant_now()->format('l, F j').' · '.$tenant->name">
        <x-slot:actions>
            @can($P::MembersManage)
                <x-button variant="secondary" icon="plus" :href="route('members.create')">Add member</x-button>
            @endcan
            @can($P::MembershipsManage)
                <x-button icon="card" :href="route('memberships.create')">Sell membership</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($setupProgress < 100 && $user->can($P::SettingsManage))
        <section class="card mb-6 overflow-hidden" x-data="{ open: true }">
            <div class="grid gap-6 p-5 md:grid-cols-[1fr_1.4fr] md:p-6">
                <div>
                    <x-badge color="brand"><x-icon name="sparkles" class="size-3.5" /> Getting started</x-badge>
                    <h2 class="mt-3 text-lg font-semibold text-zinc-900 dark:text-white">Finish setting up {{ $tenant->name }}</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $setupDone }} of {{ count($setupSteps) }} steps done.</p>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-full rounded-full bg-brand-600" style="width: {{ $setupProgress }}%"></div>
                    </div>
                </div>
                <ul class="grid gap-2 sm:grid-cols-2">
                    @foreach ($setupSteps as $step)
                        <li>
                            <a href="{{ route($step['route']) }}" @class(['flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm transition', 'border-zinc-100 text-zinc-400 dark:border-zinc-800' => $step['done'], 'border-zinc-200 font-medium text-zinc-800 hover:border-brand-300 hover:bg-brand-50/50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-brand-500/5' => ! $step['done']])>
                                @if ($step['done'])
                                    <span class="grid size-5 place-items-center rounded-full bg-emerald-500 text-white"><x-icon name="check" class="size-3" /></span>
                                    <span class="line-through">{{ $step['label'] }}</span>
                                @else
                                    <span class="size-5 rounded-full border-2 border-dashed border-zinc-300 dark:border-zinc-600"></span>
                                    <span class="flex-1">{{ $step['label'] }}</span>
                                    <x-icon name="chevron-right" class="size-4 text-zinc-400" />
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- KPI tiles --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if ($isTrainerOnly)
            <x-stat label="My members" :value="number_format($myMembers->count())" icon="users" hint="Assigned to you" />
            <x-stat label="My classes (3 days)" :value="number_format($upcomingClasses->count())" icon="calendar" hint="Upcoming sessions" />
            <x-stat label="In the gym now" :value="number_format($kpis['inGym'])" icon="qr" :hint="$kpis['todayVisits'].' visits today'" />
            <x-stat label="Active members" :value="number_format($kpis['members'])" icon="sparkles" hint="Gym-wide" />
        @else
            <x-stat label="Active members" :value="number_format($kpis['members'])" icon="users" :hint="'+'.$kpis['newMembers'].' joined this month'" />
            <x-stat label="Active memberships" :value="number_format($kpis['activeMemberships'])" icon="card" :hint="$kpis['expiring'].' expiring in 7 days'" />
            <x-stat label="Today's attendance" :value="number_format($kpis['todayVisits'])" icon="qr" :hint="$kpis['inGym'].' in the gym right now'" />
            @can($P::PaymentsView)
                <div class="card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Revenue this month</p>
                        <span class="grid size-9 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300"><x-icon name="chart" /></span>
                    </div>
                    <p class="mt-2 text-2xl font-semibold tracking-tight tabular-nums">{{ money($kpis['revenue']) }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        @if ($kpis['revenueChange'] !== null)
                            <span @class(['font-semibold', 'text-emerald-600 dark:text-emerald-400' => $kpis['revenueChange'] >= 0, 'text-rose-600 dark:text-rose-400' => $kpis['revenueChange'] < 0])>{{ $kpis['revenueChange'] >= 0 ? '▲' : '▼' }} {{ abs($kpis['revenueChange']) }}%</span> vs same point last month
                        @else
                            No revenue last month to compare
                        @endif
                    </p>
                </div>
            @endcan
        @endif
    </div>

    @unless ($isTrainerOnly)
        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @can($P::InvoicesView)
                <a href="{{ route('invoices.index', ['status' => 'overdue']) }}" class="card flex items-center gap-4 p-4 transition hover:border-brand-200 dark:hover:border-brand-500/30">
                    <span class="grid size-10 place-items-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400"><x-icon name="clipboard" /></span>
                    <div><p class="text-xs text-zinc-500 dark:text-zinc-400">Pending payments</p><p class="font-semibold tabular-nums">{{ money($kpis['outstanding']) }} <span class="text-xs font-normal text-zinc-500">· {{ $kpis['overdueInvoices'] }} overdue</span></p></div>
                </a>
            @endcan
            <a href="{{ route('memberships.index', ['state' => 'expiring']) }}" class="card flex items-center gap-4 p-4 transition hover:border-brand-200 dark:hover:border-brand-500/30">
                <span class="grid size-10 place-items-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400"><x-icon name="clock" /></span>
                <div><p class="text-xs text-zinc-500 dark:text-zinc-400">Expiring memberships</p><p class="font-semibold tabular-nums">{{ $kpis['expiring'] }} <span class="text-xs font-normal text-zinc-500">in the next 7 days</span></p></div>
            </a>
            <div class="card flex items-center gap-4 p-4">
                <span class="grid size-10 place-items-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400"><x-icon name="shield" /></span>
                <div><p class="text-xs text-zinc-500 dark:text-zinc-400">Active trainers</p><p class="font-semibold tabular-nums">{{ $kpis['trainers'] }}</p></div>
            </div>
            @can($P::EquipmentManage)
                <a href="{{ route('equipment.index') }}" class="card flex items-center gap-4 p-4 transition hover:border-brand-200 dark:hover:border-brand-500/30">
                    <span class="grid size-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"><x-icon name="wrench" /></span>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Equipment status</p>
                        <p class="text-sm font-semibold tabular-nums">{{ (int) ($kpis['equipment']['active'] ?? 0) }} active
                            <span class="font-normal text-zinc-500">· {{ (int) ($kpis['equipment']['under_maintenance'] ?? 0) + (int) ($kpis['equipment']['damaged'] ?? 0) }} need attention · {{ $kpis['maintenanceDue'] }} due</span></p>
                    </div>
                </a>
            @endcan
        </div>
    @endunless

    {{-- Charts --}}
    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        @if ($performance)
            <x-card title="Monthly performance" description="Revenue and expenses, last 6 months" class="lg:col-span-2">
                <x-slot:actions><x-button variant="ghost" size="sm" :href="route('reports.show', 'profit')">Full report</x-button></x-slot:actions>
                <x-chart title="Revenue and expenses by month" :config="['type' => 'bar', 'labels' => $performance['labels'], 'format' => 'money', 'labelHeading' => 'Month', 'series' => array_values(array_filter([
                    ['label' => 'Revenue', 'data' => $performance['revenue']],
                    $expensesByCategory !== null ? ['label' => 'Expenses', 'data' => $performance['expenses']] : null,
                ]))]" height="h-72" />
            </x-card>
        @endif

        <x-card title="Attendance" description="Daily check-ins, last 30 days" :class="$performance ? '' : 'lg:col-span-2'">
            <x-chart title="Daily check-ins" :config="['type' => 'line', 'labels' => $attendanceTrend['labels'], 'format' => 'number', 'labelHeading' => 'Day', 'series' => [['label' => 'Check-ins', 'data' => $attendanceTrend['visits']]]]" height="h-72" />
        </x-card>

        @if ($growth)
            <x-card title="Membership growth" description="New members per month">
                <x-chart title="New members per month" :config="['type' => 'bar', 'labels' => $growth['labels'], 'format' => 'number', 'labelHeading' => 'Month', 'series' => [['label' => 'New members', 'data' => $growth['joined']]]]" />
            </x-card>
        @endif

        <x-card title="Membership types" description="Active memberships by plan">
            <x-chart title="Active memberships by plan" :config="['type' => 'hbar', 'labels' => $byPlan['labels'], 'format' => 'number', 'labelHeading' => 'Plan', 'series' => [['label' => 'Active', 'data' => $byPlan['counts']]]]" />
        </x-card>

        @if ($expensesByCategory)
            <x-card title="Expenses" description="This month by category">
                <x-slot:actions><x-button variant="ghost" size="sm" :href="route('expenses.index')">View</x-button></x-slot:actions>
                <x-chart title="Expenses by category" :config="['type' => 'hbar', 'labels' => $expensesByCategory['labels'], 'format' => 'money', 'labelHeading' => 'Category', 'series' => [['label' => 'Spent', 'data' => $expensesByCategory['amounts']]]]" />
            </x-card>
        @endif
    </div>

    {{-- Lists --}}
    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-card :title="$isTrainerOnly ? 'My upcoming classes' : 'Upcoming classes'" description="Next 3 days" :padding="false">
            @can($P::ClassesView)
                <x-slot:actions><x-button variant="ghost" size="sm" :href="route('classes.index')">Schedule</x-button></x-slot:actions>
            @endcan
            @forelse ($upcomingClasses as $session)
                <a href="{{ route('classes.sessions.show', $session) }}" class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/40">
                    <span class="h-10 w-1 rounded-full" style="background: {{ $session->gymClass->color }}"></span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ $session->gymClass->name }}</p>
                        <p class="text-xs text-zinc-500">{{ $session->starts_at->copy()->setTimezone(tenant_timezone())->format('D g:i A') }} · {{ $session->trainer?->name ?? 'No trainer' }}</p>
                    </div>
                    <span class="text-xs tabular-nums text-zinc-500">{{ $session->active_bookings_count }}/{{ $session->capacity }}</span>
                </a>
            @empty
                <x-empty-state icon="calendar" title="No classes in the next 3 days" />
            @endforelse
        </x-card>

        @if ($isTrainerOnly)
            <x-card title="My members" :padding="false" class="lg:col-span-2">
                <x-slot:actions><x-button variant="ghost" size="sm" :href="route('members.index')">All</x-button></x-slot:actions>
                @forelse ($myMembers as $member)
                    <a href="{{ route('members.show', $member) }}" class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/40">
                        <x-avatar :src="$member->photo_url" :initials="$member->initials" size="size-8" />
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ $member->full_name }}</p><p class="text-xs text-zinc-500">{{ $member->currentMembership?->plan?->name ?? 'No active membership' }}</p></div>
                        <x-icon name="chevron-right" class="size-4 text-zinc-400" />
                    </a>
                @empty
                    <x-empty-state icon="users" title="No members assigned yet" description="Gym managers can assign members to you from the member profile." />
                @endforelse
            </x-card>
        @else
            <x-card title="Expiring soon" description="Memberships ending in 7 days" :padding="false">
                <x-slot:actions><x-button variant="ghost" size="sm" :href="route('memberships.index', ['state' => 'expiring'])">All</x-button></x-slot:actions>
                @forelse ($expiring as $membership)
                    <div class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3 last:border-0 dark:border-zinc-800">
                        <x-avatar :src="$membership->member->photo_url" :initials="$membership->member->initials" size="size-8" />
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('members.show', $membership->member) }}" class="block truncate text-sm font-medium hover:text-brand-600">{{ $membership->member->full_name }}</a>
                            <p class="text-xs text-zinc-500">{{ $membership->plan->name }} · {{ $membership->daysRemaining() === 0 ? 'ends today' : 'ends in '.$membership->daysRemaining().'d' }}</p>
                        </div>
                        @can($P::MembershipsManage)<x-button size="sm" variant="secondary" :href="route('memberships.renew', $membership)">Renew</x-button>@endcan
                    </div>
                @empty
                    <x-empty-state icon="check-circle" title="Nothing expiring this week" />
                @endforelse
            </x-card>

            <x-card title="Recent activity" :padding="false">
                @can($P::AuditView)
                    <x-slot:actions><x-button variant="ghost" size="sm" :href="route('audit-logs.index')">Audit log</x-button></x-slot:actions>
                @endcan
                @if ($recentActivity->isNotEmpty())
                    @include('partials.activity-list', ['activity' => $recentActivity])
                @else
                    @forelse ($announcements as $announcement)
                        <div class="border-b border-zinc-100 px-5 py-3 last:border-0 dark:border-zinc-800">
                            <p class="text-sm font-medium">{{ $announcement->title }}</p>
                            <p class="mt-0.5 line-clamp-2 text-xs text-zinc-500">{{ $announcement->body }}</p>
                        </div>
                    @empty
                        <x-empty-state icon="megaphone" title="No announcements" />
                    @endforelse
                @endif
            </x-card>
        @endif
    </div>
</x-layouts.app>
