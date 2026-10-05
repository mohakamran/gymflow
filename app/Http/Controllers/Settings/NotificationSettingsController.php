<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationSettingsController extends Controller
{
    public const TOGGLES = [
        'membership_expiry' => ['Membership expiry reminders', 'Email members before their membership ends.'],
        'renewals' => ['Membership confirmations', 'Confirm new and renewed memberships to the member.'],
        'payment_receipts' => ['Payment receipts', 'Send a receipt when a payment is recorded.'],
        'payment_due' => ['Payment due reminders', 'Remind members about overdue invoices (weekly).'],
        'class_reminders' => ['Class reminders', 'Remind booked members a few hours before class.'],
        'announcements' => ['Announcements', 'Allow announcements to be emailed.'],
        'maintenance' => ['Equipment maintenance', 'Alert owners when equipment is due for service.'],
    ];

    public function edit(): View
    {
        return view('settings.notifications', ['tenant' => tenant(), 'toggles' => self::TOGGLES]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [
            'expiry_reminder_days' => ['required', 'integer', 'min:1', 'max:30'],
            'require_active_membership' => ['boolean'],
            'sms' => ['boolean'],
            'whatsapp' => ['boolean'],
        ];
        foreach (array_keys(self::TOGGLES) as $key) {
            $rules[$key] = ['boolean'];
        }

        $request->merge(collect($rules)->filter(fn ($rule) => in_array('boolean', $rule, true))->keys()->mapWithKeys(fn ($key) => [$key => $request->boolean($key)])->all());
        $data = $request->validate($rules);

        $tenant = tenant();
        $notifications = collect($data)->except('require_active_membership')->map(fn ($value, $key) => $key === 'expiry_reminder_days' ? (int) $value : (bool) $value)->all();
        $tenant->settings = array_replace_recursive($tenant->settings ?? [], [
            'notifications' => $notifications,
            'attendance' => ['require_active_membership' => (bool) $data['require_active_membership']],
        ]);
        $tenant->save();

        return $this->done(redirect()->route('settings.notifications.edit'), 'Notification settings saved.');
    }
}
