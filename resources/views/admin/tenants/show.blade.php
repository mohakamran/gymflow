<x-layouts.app :title="$tenant->name">
    <x-page-header :title="$tenant->name" :description="'Workspace /'.$tenant->slug.' · created '.$tenant->created_at->toFormattedDateString()">
        <x-slot:actions><x-button variant="secondary" :href="route('admin.tenants.index')">Back to gyms</x-button></x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Details">
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-zinc-500">Contact email</dt><dd class="mt-0.5 font-medium">{{ $tenant->email ?? '—' }}</dd></div>
                    <div><dt class="text-zinc-500">Phone</dt><dd class="mt-0.5 font-medium">{{ $tenant->phone ?? '—' }}</dd></div>
                    <div><dt class="text-zinc-500">Location</dt><dd class="mt-0.5 font-medium">{{ collect([$tenant->city, $tenant->country])->filter()->join(', ') ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">Currency / timezone</dt><dd class="mt-0.5 font-medium">{{ $tenant->currency }} · {{ $tenant->timezone }}</dd></div>
                    <div><dt class="text-zinc-500">Users</dt><dd class="mt-0.5 font-medium">{{ $tenant->users_count }}</dd></div>
                    <div><dt class="text-zinc-500">Owner(s)</dt><dd class="mt-0.5 font-medium">{{ $owners->map(fn ($o) => $o->name.' <'.$o->email.'>')->join(', ') ?: '—' }}</dd></div>
                </dl>
            </x-card>
            <x-card title="Recent activity" :padding="false">
                @include('partials.activity-list', ['activity' => $activity])
            </x-card>
        </div>

        <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}">
            @csrf @method('PATCH')
            <x-card title="Subscription" description="Suspending a gym signs out all of its users immediately.">
                <div class="space-y-5">
                    <x-select name="status" label="Status" :options="collect(\App\Enums\TenantStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="$tenant->status->value" />
                    <x-select name="subscription_plan" label="Plan" :options="collect(\App\Enums\SubscriptionPlan::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()])" :value="$tenant->subscription_plan->value" />
                    <x-input name="trial_ends_at" type="date" label="Trial ends" :value="$tenant->trial_ends_at?->toDateString()" />
                </div>
                <x-slot:footer><x-button>Save</x-button></x-slot:footer>
            </x-card>
        </form>
    </div>
</x-layouts.app>
