<x-layouts.app title="SaaS plans">
    <x-page-header title="SaaS plans" description="Subscription tiers, limits and change requests from gyms." />
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($plans as $plan)
            <div class="card p-5">
                <div class="flex items-center justify-between"><h3 class="font-semibold">{{ $plan->label() }}</h3><x-badge color="brand">{{ (int) ($distribution[$plan->value] ?? 0) }} gyms</x-badge></div>
                <p class="mt-2 text-2xl font-semibold">{{ $plan->monthlyPrice() ? '$'.$plan->monthlyPrice().'/mo' : 'Custom' }}</p>
                <dl class="mt-3 space-y-1 text-sm"><div class="flex justify-between"><dt class="text-zinc-500">Members</dt><dd>{{ $plan->memberLimit() ?? 'Unlimited' }}</dd></div><div class="flex justify-between"><dt class="text-zinc-500">Team accounts</dt><dd>{{ $plan->staffLimit() ?? 'Unlimited' }}</dd></div></dl>
            </div>
        @endforeach
    </div>
    <p class="mt-3 text-xs text-zinc-500">Plan limits are defined in <code>app/Enums/SubscriptionPlan.php</code>.</p>

    <x-card title="Plan change requests" class="mt-6" :padding="false">
        @if ($requests->isEmpty())
            <x-empty-state icon="sparkles" title="No requests yet" />
        @else
            <div class="overflow-x-auto">
                <table class="table-default">
                    <thead><tr><th>Gym</th><th>Change</th><th>Requested by</th><th>When</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($requests as $change)
                            <tr>
                                <td><a href="{{ route('admin.tenants.show', $change->tenant) }}" class="font-medium hover:text-brand-600">{{ $change->tenant->name }}</a>@if ($change->message)<p class="text-xs text-zinc-500">{{ $change->message }}</p>@endif</td>
                                <td>{{ $change->current_plan->label() }} → <strong>{{ $change->requested_plan->label() }}</strong></td>
                                <td>{{ $change->requester?->name }}</td>
                                <td class="whitespace-nowrap">{{ $change->created_at->diffForHumans() }}</td>
                                <td><x-badge :color="match ($change->status) { 'pending' => 'amber', 'approved' => 'emerald', 'rejected' => 'rose', default => 'zinc' }">{{ ucfirst($change->status) }}</x-badge></td>
                                <td class="text-right whitespace-nowrap">
                                    @if ($change->status === 'pending')
                                        <form method="POST" action="{{ route('admin.plans.resolve', $change->id) }}" class="inline">@csrf @method('PATCH')<input type="hidden" name="decision" value="approved"><x-button size="sm">Approve</x-button></form>
                                        <form method="POST" action="{{ route('admin.plans.resolve', $change->id) }}" class="inline">@csrf @method('PATCH')<input type="hidden" name="decision" value="rejected"><x-button size="sm" variant="ghost">Reject</x-button></form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layouts.app>
