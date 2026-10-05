@php $editing = $member->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit '.$member->full_name : 'Add member'">
    <x-page-header :title="$editing ? 'Edit '.$member->full_name : 'Add member'" :description="$editing ? $member->member_code : 'A member code and check-in QR code are generated automatically.'">
        <x-slot:actions><x-button variant="secondary" icon="arrow-left" :href="$editing ? route('members.show', $member) : route('members.index')">Back</x-button></x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('members.update', $member) : route('members.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[280px_1fr]">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-card title="Photo">
            <div x-data="imagePreview(@js($member->photo_url))" class="flex flex-col items-center gap-4 text-center">
                <div class="grid size-32 place-items-center overflow-hidden rounded-full bg-zinc-100 ring-4 ring-white dark:bg-zinc-800 dark:ring-zinc-900">
                    <template x-if="preview"><img :src="preview" alt="" class="size-full object-cover"></template>
                    <template x-if="!preview"><x-icon name="user" class="size-12 text-zinc-400" /></template>
                </div>
                <div class="flex gap-2">
                    <label class="inline-flex cursor-pointer items-center rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 ring-zinc-300 ring-inset hover:bg-zinc-50 dark:ring-zinc-700 dark:hover:bg-zinc-800">
                        Upload <input x-ref="input" type="file" name="photo" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pick">
                    </label>
                    <button type="button" x-show="preview" @click="clear" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10">Remove</button>
                </div>
                <input type="hidden" name="remove_photo" :value="removed ? 1 : 0" value="0">
                @error('photo')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                <p class="text-xs text-zinc-500">PNG, JPG or WebP up to 2 MB.</p>
            </div>
        </x-card>

        <div class="space-y-6">
            <x-card title="Personal details">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="first_name" label="First name" :value="$member->first_name" required autofocus />
                    <x-input name="last_name" label="Last name" :value="$member->last_name" required />
                    <x-input name="email" type="email" label="Email" :value="$member->email" hint="Used for receipts, reminders and portal access." />
                    <x-input name="phone" type="tel" label="Phone" :value="$member->phone" />
                    <x-input name="date_of_birth" type="date" label="Date of birth" :value="$member->date_of_birth?->toDateString()" />
                    <x-select name="gender" label="Gender" :options="\App\Enums\Gender::options()" :value="$member->gender?->value" placeholder="—" />
                    <div class="sm:col-span-2"><x-input name="address" label="Address" :value="$member->address" /></div>
                </div>
            </x-card>

            <x-card title="Emergency contact">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="emergency_contact_name" label="Name" :value="$member->emergency_contact_name" />
                    <x-input name="emergency_contact_phone" type="tel" label="Phone" :value="$member->emergency_contact_phone" />
                </div>
            </x-card>

            <x-card title="Membership details">
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-input name="joined_on" type="date" label="Joining date" :value="$member->joined_on?->toDateString()" required />
                    <x-select name="status" label="Status" :options="\App\Enums\MemberStatus::options()" :value="$member->status?->value" required />
                    <x-select name="trainer_id" label="Assigned trainer" :options="$trainers" :value="$member->trainer_id" placeholder="None" />
                    <div class="sm:col-span-3"><x-textarea name="notes" label="Internal notes" :value="$member->notes" rows="3" hint="Visible to staff only — injuries, preferences, goals." /></div>
                </div>
                <x-slot:footer>
                    <x-button variant="secondary" :href="$editing ? route('members.show', $member) : route('members.index')">Cancel</x-button>
                    <x-button>{{ $editing ? 'Save changes' : 'Add member' }}</x-button>
                </x-slot:footer>
            </x-card>
        </div>
    </form>
</x-layouts.app>
