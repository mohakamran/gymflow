<x-layouts.app :title="$member->full_name">
    @php
        $user = auth()->user();
        $P = \App\Enums\Permission::class;
        $canManage = $user->can('update', $member);
        $canCoach = $user->can('coach', $member);
        $current = $member->currentMembership;
        $display = $current?->displayStatus();
    @endphp

    <div class="mb-4"><a href="{{ route('members.index') }}" class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200"><x-icon name="arrow-left" class="size-4" /> Members</a></div>

    {{-- Profile header --}}
    <section class="card mb-6 overflow-hidden">
        <div class="h-20 bg-gradient-to-r from-brand-600 to-brand-800"></div>
        <div class="flex flex-wrap items-end gap-5 px-5 pb-5 sm:px-6">
            <x-avatar :src="$member->photo_url" :initials="$member->initials" size="size-24" class="-mt-12 text-2xl ring-4" />
            <div class="min-w-0 flex-1 pt-3">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight">{{ $member->full_name }}</h1>
                    @if ($member->status !== \App\Enums\MemberStatus::Active)
                        <x-status-badge :status="$member->status" />
                    @elseif ($display)
                        <x-badge :color="$display['color']">{{ $display['label'] }}</x-badge>
                    @endif
                    @if ($member->user_id)<x-badge color="brand">Portal access</x-badge>@endif
                </div>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $member->member_code }} · Member since {{ format_date($member->joined_on) }}@if ($member->trainer) · Trainer: {{ $member->trainer->name }}@endif</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-button variant="secondary" size="sm" icon="qr" :href="route('members.card', $member)">Member card</x-button>
                @if ($canManage)
                    <x-button variant="secondary" size="sm" icon="pencil" :href="route('members.edit', $member)">Edit</x-button>
                @endif
                @can($P::MembershipsManage)
                    @if ($current)
                        <x-button size="sm" icon="refresh" :href="route('memberships.renew', $current)">Renew</x-button>
                    @else
                        <x-button size="sm" icon="card" :href="route('memberships.create', ['member' => $member->id])">Sell membership</x-button>
                    @endif
                @endcan
            </div>
        </div>
    </section>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Current plan" :value="$current?->plan?->name ?? 'None'" icon="card" :hint="$current ? ($current->status === \App\Enums\MembershipStatus::Suspended ? 'Frozen since '.format_date($current->suspended_on) : $current->daysRemaining().' days left · ends '.format_date($current->ends_on)) : 'Sell a membership to get started'" />
        <x-stat label="Visits this month" :value="$visitsThisMonth" icon="qr" :hint="number_format($totalVisits).' visits all-time'" />
        @can($P::PaymentsView)
            <x-stat label="Outstanding" :value="money($outstanding)" icon="clipboard" :hint="$outstanding > 0 ? 'Unpaid invoices' : 'All paid up'" />
            <x-stat label="Lifetime value" :value="money($lifetimeValue)" icon="chart" hint="Total paid, net of refunds" />
        @endcan
    </div>

    @php
        $tabs = ['overview' => 'Overview', 'memberships' => 'Memberships'];
        if ($user->can($P::InvoicesView)) { $tabs['billing'] = 'Billing'; }
        $tabs['attendance'] = 'Attendance';
        $tabs['workouts'] = 'Workouts';
        $tabs['progress'] = 'Progress';
        if ($canCoach) { $tabs['notes'] = 'Notes'; }
        if ($canManage) { $tabs['documents'] = 'Documents'; }
    @endphp

    <x-tabs :tabs="$tabs">
        {{-- Overview --}}
        <div x-show="tab === 'overview'" class="grid gap-6 lg:grid-cols-3">
            <x-card title="Contact & personal" class="lg:col-span-2">
                <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    @foreach ([
                        'Email' => $member->email, 'Phone' => $member->phone,
                        'Date of birth' => $member->date_of_birth ? format_date($member->date_of_birth).' ('.$member->date_of_birth->age.')' : null,
                        'Gender' => $member->gender?->label(), 'Address' => $member->address,
                        'Emergency contact' => trim(($member->emergency_contact_name ?? '').' '.($member->emergency_contact_phone ?? '')),
                    ] as $label => $value)
                        <div><dt class="text-zinc-500 dark:text-zinc-400">{{ $label }}</dt><dd class="mt-0.5 font-medium break-words">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                    @if ($member->notes)
                        <div class="sm:col-span-2"><dt class="text-zinc-500">Staff notes</dt><dd class="mt-0.5 whitespace-pre-line">{{ $member->notes }}</dd></div>
                    @endif
                </dl>
            </x-card>

            <div class="space-y-6">
                @if ($current)
                    <x-card title="Membership">
                        <p class="text-lg font-semibold">{{ $current->plan->name }}</p>
                        <p class="text-sm text-zinc-500">{{ format_date($current->starts_on) }} – {{ format_date($current->ends_on) }}</p>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"><div class="h-full rounded-full bg-brand-600" style="width: {{ $current->progressPercent() }}%"></div></div>
                        <p class="mt-1.5 text-xs text-zinc-500">{{ $current->daysRemaining() }} days remaining</p>
                    </x-card>
                @endif

                @if ($canManage)
                    <x-card title="Member portal" description="Let the member view their membership, invoices, classes and progress online.">
                        @if ($member->user_id)
                            <p class="text-sm">Signed in as <span class="font-medium">{{ $member->user->email }}</span>@if ($member->user->last_login_at) · last seen {{ $member->user->last_login_at->diffForHumans() }}@endif</p>
                            <x-confirm :action="route('members.portal.revoke', $member)" method="DELETE" title="Remove portal access?" message="The member will no longer be able to sign in. Their data is kept." trigger="Remove access" confirm="Remove access" class="mt-3" />
                        @else
                            <form method="POST" action="{{ route('members.portal.invite', $member) }}">
                                @csrf
                                <x-button size="sm" icon="mail" :disabled="! $member->email">Send portal invitation</x-button>
                                @unless ($member->email)<p class="mt-2 text-xs text-zinc-500">Add an email address first.</p>@endunless
                            </form>
                        @endif
                    </x-card>
                @endif

                <x-card title="Upcoming classes" :padding="false">
                    @forelse ($member->bookings->filter(fn ($b) => $b->session && $b->session->starts_at->isFuture() && $b->status === \App\Enums\BookingStatus::Booked) as $booking)
                        <a href="{{ route('classes.sessions.show', $booking->session) }}" class="flex items-center justify-between border-b border-zinc-100 px-5 py-3 text-sm last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/40">
                            <span class="font-medium">{{ $booking->session->gymClass->name }}</span>
                            <span class="text-zinc-500">{{ format_date($booking->session->starts_at, true) }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-4 text-sm text-zinc-500">No upcoming bookings.</p>
                    @endforelse
                </x-card>
            </div>
        </div>

        {{-- Memberships --}}
        <div x-show="tab === 'memberships'" x-cloak>
            <x-card title="Membership history" :padding="false">
                @can($P::MembershipsManage)
                    <x-slot:actions><x-button size="sm" icon="plus" :href="route('memberships.create', ['member' => $member->id])">Sell membership</x-button></x-slot:actions>
                @endcan
                @if ($member->memberships->isEmpty())
                    <x-empty-state icon="card" title="No memberships yet" />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-default">
                            <thead><tr><th>Plan</th><th>Period</th><th class="text-right">Price</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
                            <tbody>
                                @foreach ($member->memberships as $membership)
                                    @php $d = $membership->displayStatus(); @endphp
                                    <tr>
                                        <td class="font-medium">{{ $membership->plan->name }}@if ($membership->renewed_from_id)<span class="ml-1 text-xs text-zinc-400">renewal</span>@endif</td>
                                        <td class="whitespace-nowrap">{{ format_date($membership->starts_on) }} – {{ format_date($membership->ends_on) }}</td>
                                        <td class="text-right tabular-nums">{{ money($membership->price - $membership->discount_amount) }}</td>
                                        <td><x-badge :color="$d['color']">{{ $d['label'] }}</x-badge></td>
                                        <td class="text-right whitespace-nowrap">
                                            @can('update', $membership)
                                                @if ($membership->status === \App\Enums\MembershipStatus::Active)
                                                    <x-confirm :action="route('memberships.suspend', $membership)" title="Freeze this membership?" message="Time stops counting while frozen. The end date is extended when you resume it." trigger="Freeze" confirm="Freeze" variant="primary" />
                                                @elseif ($membership->status === \App\Enums\MembershipStatus::Suspended)
                                                    <form method="POST" action="{{ route('memberships.resume', $membership) }}" class="inline">@csrf<x-button size="sm" variant="ghost">Resume</x-button></form>
                                                @endif
                                                @if (in_array($membership->status, [\App\Enums\MembershipStatus::Active, \App\Enums\MembershipStatus::Pending, \App\Enums\MembershipStatus::Suspended], true))
                                                    <x-confirm :action="route('memberships.cancel', $membership)" title="Cancel this membership?" message="The member loses access immediately. Invoices are not changed — refund separately if needed." trigger="Cancel" confirm="Cancel membership" />
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        {{-- Billing --}}
        @can($P::InvoicesView)
            <div x-show="tab === 'billing'" x-cloak class="grid gap-6 lg:grid-cols-2">
                <x-card title="Invoices" :padding="false">
                    @can($P::InvoicesManage)
                        <x-slot:actions><x-button size="sm" variant="secondary" icon="plus" :href="route('invoices.create', ['member' => $member->id])">New invoice</x-button></x-slot:actions>
                    @endcan
                    @forelse ($member->invoices as $invoice)
                        <a href="{{ route('invoices.show', $invoice) }}" class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/40">
                            <div class="min-w-0 flex-1"><p class="text-sm font-medium">{{ $invoice->number }}</p><p class="text-xs text-zinc-500">{{ format_date($invoice->issued_on) }}</p></div>
                            <span class="text-sm tabular-nums">{{ money($invoice->total, $invoice->currency) }}</span>
                            <x-status-badge :status="$invoice->status" />
                        </a>
                    @empty
                        <x-empty-state icon="clipboard" title="No invoices" />
                    @endforelse
                </x-card>
                <x-card title="Payments" :padding="false">
                    @can($P::PaymentsManage)
                        <x-slot:actions><x-button size="sm" variant="secondary" icon="plus" :href="route('payments.create', ['member' => $member->id])">Record payment</x-button></x-slot:actions>
                    @endcan
                    @forelse ($member->payments as $payment)
                        <a href="{{ route('payments.show', $payment) }}" class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/40">
                            <div class="min-w-0 flex-1"><p class="text-sm font-medium">{{ $payment->method->label() }}</p><p class="text-xs text-zinc-500">{{ format_date($payment->paid_at) }} · {{ $payment->reference }}</p></div>
                            <span class="text-sm tabular-nums">{{ money($payment->amount) }}</span>
                            <x-status-badge :status="$payment->status" />
                        </a>
                    @empty
                        <x-empty-state icon="card" title="No payments" />
                    @endforelse
                </x-card>
            </div>
        @endcan

        {{-- Attendance --}}
        <div x-show="tab === 'attendance'" x-cloak>
            <x-card title="Recent visits" :padding="false">
                @if ($member->attendances->isEmpty())
                    <x-empty-state icon="qr" title="No visits recorded" />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-default">
                            <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Duration</th><th>Method</th></tr></thead>
                            <tbody>
                                @foreach ($member->attendances as $visit)
                                    <tr>
                                        <td>{{ format_date($visit->checked_in_at->copy()->setTimezone(tenant_timezone())) }}</td>
                                        <td class="tabular-nums">{{ format_time($visit->checked_in_at) }}</td>
                                        <td class="tabular-nums">{{ $visit->checked_out_at ? format_time($visit->checked_out_at) : '—' }}</td>
                                        <td>{{ $visit->durationMinutes() !== null ? $visit->durationMinutes().' min' : 'In gym' }}</td>
                                        <td>{{ $visit->method->label() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        {{-- Workouts --}}
        <div x-show="tab === 'workouts'" x-cloak id="workouts">
            <x-card title="Workout plans" :padding="false">
                @if ($canCoach)
                    <x-slot:actions><x-button size="sm" icon="plus" :href="route('members.workouts.create', $member)">New plan</x-button></x-slot:actions>
                @endif
                @forelse ($member->workoutPlans as $plan)
                    <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 px-5 py-3.5 last:border-0 dark:border-zinc-800">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('members.workouts.show', [$member, $plan]) }}" class="font-medium hover:text-brand-600">{{ $plan->title }}</a>
                            <p class="text-xs text-zinc-500">{{ $plan->goal ? $plan->goal.' · ' : '' }}{{ count($plan->exercises ?? []) }} exercises · by {{ $plan->trainer?->name ?? 'Staff' }}</p>
                        </div>
                        @if ($plan->is_active)<x-badge color="emerald">Active</x-badge>@else<x-badge>Archived</x-badge>@endif
                        @can('update', $plan)
                            <x-button size="sm" variant="ghost" :href="route('members.workouts.edit', [$member, $plan])">Edit</x-button>
                            <x-confirm :action="route('members.workouts.destroy', [$member, $plan])" method="DELETE" title="Delete this workout plan?" trigger="Delete" confirm="Delete" />
                        @endcan
                    </div>
                @empty
                    <x-empty-state icon="bolt" title="No workout plans" description="Trainers can build a structured plan with sets, reps and rest." />
                @endforelse
            </x-card>
        </div>

        {{-- Progress --}}
        <div x-show="tab === 'progress'" x-cloak id="progress" class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                @php $weights = $member->progressRecords->whereNotNull('weight_kg'); @endphp
                @if ($weights->count() > 1)
                    <x-card title="Weight trend">
                        <x-chart title="Weight over time" :config="['type' => 'line', 'labels' => $weights->map(fn ($r) => $r->recorded_on->format('M j'))->values()->all(), 'format' => 'number', 'labelHeading' => 'Date', 'series' => [['label' => 'Weight (kg)', 'data' => $weights->map(fn ($r) => (float) $r->weight_kg)->values()->all()]]]" height="h-56" />
                    </x-card>
                @endif
                <x-card title="Measurements" :padding="false">
                    @if ($member->progressRecords->isEmpty())
                        <x-empty-state icon="chart" title="No measurements yet" />
                    @else
                        <div class="overflow-x-auto">
                            <table class="table-default">
                                <thead><tr><th>Date</th>@foreach ($measurements as $label)<th class="text-right">{{ $label }}</th>@endforeach<th></th></tr></thead>
                                <tbody>
                                    @foreach ($member->progressRecords->sortByDesc('recorded_on') as $record)
                                        <tr>
                                            <td class="whitespace-nowrap">{{ format_date($record->recorded_on) }}</td>
                                            @foreach (array_keys($measurements) as $field)<td class="text-right tabular-nums">{{ $record->$field !== null ? rtrim(rtrim($record->$field, '0'), '.') : '—' }}</td>@endforeach
                                            <td class="text-right">@if ($canCoach)<x-confirm :action="route('members.progress.destroy', [$member, $record])" method="DELETE" title="Remove this measurement?" trigger="Remove" confirm="Remove" />@endif</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            </div>
            @if ($canCoach)
                <form method="POST" action="{{ route('members.progress.store', $member) }}">
                    @csrf
                    <x-card title="Record measurements">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2"><x-input name="recorded_on" type="date" label="Date" :value="tenant_today()->toDateString()" required /></div>
                            @foreach ($measurements as $field => $label)
                                <x-input :name="$field" type="number" step="0.1" min="0" :label="$label" />
                            @endforeach
                            <div class="col-span-2"><x-textarea name="notes" label="Notes" rows="2" /></div>
                        </div>
                        <x-slot:footer><x-button>Save</x-button></x-slot:footer>
                    </x-card>
                </form>
            @endif
        </div>

        {{-- Notes --}}
        @if ($canCoach)
            <div x-show="tab === 'notes'" x-cloak id="notes" class="grid gap-6 lg:grid-cols-3">
                <x-card title="Notes" :padding="false" class="lg:col-span-2">
                    @forelse ($member->memberNotes as $note)
                        <div class="flex gap-3 border-b border-zinc-100 px-5 py-4 last:border-0 dark:border-zinc-800">
                            <x-avatar :user="$note->author" size="size-8" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm"><span class="font-medium">{{ $note->author?->name ?? 'Staff' }}</span> <span class="text-xs text-zinc-500">{{ $note->created_at->diffForHumans() }}</span></p>
                                <p class="mt-1 text-sm whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $note->body }}</p>
                            </div>
                            @if ($note->user_id === $user->id || $canManage)
                                <x-confirm :action="route('members.notes.destroy', [$member, $note])" method="DELETE" title="Delete this note?" trigger="Delete" confirm="Delete" />
                            @endif
                        </div>
                    @empty
                        <x-empty-state icon="pencil" title="No notes yet" />
                    @endforelse
                </x-card>
                <form method="POST" action="{{ route('members.notes.store', $member) }}">
                    @csrf
                    <x-card title="Add note">
                        <x-textarea name="body" rows="5" placeholder="Session notes, goals, injuries…" required />
                        <x-slot:footer><x-button>Add note</x-button></x-slot:footer>
                    </x-card>
                </form>
            </div>
        @endif

        {{-- Documents --}}
        @if ($canManage)
            <div x-show="tab === 'documents'" x-cloak id="documents" class="grid gap-6 lg:grid-cols-3">
                <x-card title="Documents" description="Waivers, medical forms, ID — stored privately." :padding="false" class="lg:col-span-2">
                    @forelse ($member->documents as $document)
                        <div class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3 last:border-0 dark:border-zinc-800">
                            <span class="grid size-9 place-items-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800"><x-icon name="clipboard" class="size-4" /></span>
                            <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ $document->name }}</p><p class="text-xs text-zinc-500">{{ $document->humanSize() }} · {{ $document->created_at->diffForHumans() }}</p></div>
                            <x-button size="sm" variant="ghost" icon="download" :href="route('members.documents.show', [$member, $document])">Download</x-button>
                            <x-confirm :action="route('members.documents.destroy', [$member, $document])" method="DELETE" title="Delete this document?" trigger="Delete" confirm="Delete" />
                        </div>
                    @empty
                        <x-empty-state icon="clipboard" title="No documents" />
                    @endforelse
                </x-card>
                <form method="POST" action="{{ route('members.documents.store', $member) }}" enctype="multipart/form-data">
                    @csrf
                    <x-card title="Upload document">
                        <div class="space-y-4">
                            <x-input name="name" label="Name" placeholder="e.g. Liability waiver" />
                            <div class="space-y-1.5">
                                <input type="file" name="document" required class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-semibold dark:text-zinc-400 dark:file:bg-zinc-800 dark:file:text-zinc-200">
                                @error('document')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                <p class="text-xs text-zinc-500">PDF, image or Word, up to 5 MB.</p>
                            </div>
                        </div>
                        <x-slot:footer><x-button icon="download">Upload</x-button></x-slot:footer>
                    </x-card>
                </form>
            </div>
        @endif
    </x-tabs>

    @if ($user->can('delete', $member))
        <div class="mt-10 flex justify-end">
            <x-confirm :action="route('members.destroy', $member)" method="DELETE" title="Archive {{ $member->full_name }}?" message="The member is hidden from lists and their portal access is removed. Financial records are kept." trigger="Archive member" confirm="Archive" />
        </div>
    @endif
</x-layouts.app>
