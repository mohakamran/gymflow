<x-layouts.app title="Notification settings">
    <x-page-header title="Settings" description="Manage how your gym appears to members and on invoices." />
    @include('settings._nav')
    <form method="POST" action="{{ route('settings.notifications.update') }}" class="space-y-6">
        @csrf @method('PUT')
        <x-card title="Automatic notifications" description="Emails go to members (and owners for maintenance). In-app notifications are always recorded.">
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($toggles as $key => [$label, $description])
                    <div class="py-4 first:pt-0 last:pb-0"><x-toggle :name="$key" :label="$label" :description="$description" :checked="$tenant->setting('notifications.'.$key, true)" /></div>
                @endforeach
            </div>
            <div class="mt-6 max-w-xs"><x-input name="expiry_reminder_days" type="number" min="1" max="30" label="Send expiry reminders this many days ahead" :value="$tenant->setting('notifications.expiry_reminder_days', 7)" required /></div>
        </x-card>
        <x-card title="Text messaging" description="SMS and WhatsApp use the provider configured by your platform administrator.">
            <div class="space-y-4">
                <x-toggle name="sms" label="SMS" :checked="$tenant->setting('notifications.sms', false)" description="Short texts for receipts, reminders and class alerts." />
                <x-toggle name="whatsapp" label="WhatsApp" :checked="$tenant->setting('notifications.whatsapp', false)" />
            </div>
        </x-card>
        <x-card title="Check-in rules">
            <x-toggle name="require_active_membership" label="Require an active membership to check in" :checked="$tenant->setting('attendance.require_active_membership', true)" description="When off, any active member can check in even if their membership has lapsed." />
            <x-slot:footer><x-button>Save settings</x-button></x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
