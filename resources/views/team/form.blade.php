@php $editing = $user->exists; $hours = $profile?->working_hours ?? []; @endphp
<x-layouts.app :title="$editing ? 'Edit '.$user->name : 'Invite team member'">
    <x-page-header :title="$editing ? 'Edit '.$user->name : 'Invite a team member'" :description="$editing ? $user->email : 'They receive an email with a link to set their password.'">
        <x-slot:actions><x-button variant="secondary" icon="arrow-left" :href="route('staff.index')">Back</x-button></x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('staff.update', $user) : route('staff.store') }}" class="grid gap-6 lg:grid-cols-2">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="space-y-6">
            <x-card title="Account">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2"><x-input name="name" label="Full name" :value="$user->name" required autofocus /></div>
                    @if ($editing)
                        <div class="sm:col-span-2"><p class="text-sm text-zinc-500">Email: <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $user->email }}</span> (the user can change it from their profile)</p></div>
                    @else
                        <div class="sm:col-span-2"><x-input name="email" type="email" label="Email" required /></div>
                    @endif
                    <x-input name="phone" type="tel" label="Phone" :value="$user->phone" />
                    <x-select name="role" label="Role" :options="['staff' => 'Staff / Reception', 'trainer' => 'Trainer', 'owner' => 'Owner (full access)']" :value="$role" required />
                </div>
                <div class="mt-5 rounded-xl bg-zinc-50 p-4 text-xs text-zinc-600 dark:bg-zinc-800/50 dark:text-zinc-400">
                    <p><strong>Staff</strong> — members, check-in, memberships, payments & invoices.</p>
                    <p class="mt-1"><strong>Trainer</strong> — assigned members, workout plans, progress, classes.</p>
                    <p class="mt-1"><strong>Owner</strong> — everything, including settings, reports and finances.</p>
                </div>
            </x-card>
            <x-card title="Profile" description="Trainer profiles can appear on your public gym page.">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="job_title" label="Job title" :value="$profile?->job_title" placeholder="e.g. Head Coach" />
                    <x-input name="specialization" label="Specialization" :value="$profile?->specialization" placeholder="e.g. Strength, mobility" />
                    <x-input name="hired_on" type="date" label="Start date" :value="$profile?->hired_on?->toDateString()" />
                    <x-input name="hourly_rate" type="number" step="0.01" min="0" label="Hourly rate" :value="$profile?->hourly_rate" />
                    <div class="sm:col-span-2"><x-textarea name="bio" label="Bio" :value="$profile?->bio" rows="3" /></div>
                    <div class="sm:col-span-2"><x-toggle name="is_public" label="Show on public page" :checked="$profile?->is_public ?? true" /></div>
                </div>
            </x-card>
        </div>
        <x-card title="Working schedule" description="Leave a day empty if they don't work it.">
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach (\App\Models\Tenant::DAYS as $day)
                    <div class="flex flex-wrap items-center gap-3 py-3">
                        <span class="w-24 text-sm font-medium capitalize">{{ $day }}</span>
                        <input type="time" name="working_hours[{{ $day }}][start]" value="{{ old("working_hours.$day.start", $hours[$day]['start'] ?? '') }}" class="form-control w-32" aria-label="{{ $day }} start">
                        <span class="text-zinc-400">–</span>
                        <input type="time" name="working_hours[{{ $day }}][end]" value="{{ old("working_hours.$day.end", $hours[$day]['end'] ?? '') }}" class="form-control w-32" aria-label="{{ $day }} end">
                    </div>
                @endforeach
            </div>
            <x-slot:footer><x-button>{{ $editing ? 'Save changes' : 'Send invitation' }}</x-button></x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
