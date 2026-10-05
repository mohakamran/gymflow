<x-layouts.app title="Your profile">
    <x-page-header title="Your profile" description="Your personal account details and password." />

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf @method('PATCH')
            <x-card title="Account">
                <div class="space-y-5">
                    <div x-data="imagePreview(@js($user->avatar_url))" class="flex items-center gap-4">
                        <template x-if="preview"><img :src="preview" alt="" class="size-16 rounded-full object-cover"></template>
                        <template x-if="!preview"><x-avatar :user="$user" size="size-16" class="text-base" /></template>
                        <label class="inline-flex cursor-pointer items-center rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 ring-zinc-300 ring-inset hover:bg-zinc-50 dark:ring-zinc-700 dark:hover:bg-zinc-800">
                            Change photo <input x-ref="input" type="file" name="avatar" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pick">
                        </label>
                        @error('avatar')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <x-input name="name" label="Full name" :value="$user->name" required />
                    <x-input name="email" type="email" label="Email" :value="$user->email" hint="Changing your email requires re-verification." required />
                    <x-input name="phone" type="tel" label="Phone" :value="$user->phone" />
                </div>
                <x-slot:footer><x-button>Save profile</x-button></x-slot:footer>
            </x-card>
        </form>

        <form method="POST" action="{{ route('profile.password') }}">
            @csrf @method('PUT')
            <x-card title="Password" description="Use a long, unique password.">
                <div class="space-y-5">
                    <x-input name="current_password" type="password" label="Current password" autocomplete="current-password" bag="password" required />
                    <x-input name="password" type="password" label="New password" autocomplete="new-password" bag="password" required />
                    <x-input name="password_confirmation" type="password" label="Confirm new password" autocomplete="new-password" bag="password" required />
                </div>
                <x-slot:footer><x-button>Update password</x-button></x-slot:footer>
            </x-card>
        </form>
    </div>
</x-layouts.app>
