<?php

namespace App\Notifications;

use App\Models\Membership;
use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;

class MembershipRenewedNotification extends GymNotification
{
    public function __construct(Tenant $tenant, public Membership $membership)
    {
        parent::__construct($tenant);
    }

    public static function preferenceKey(): ?string
    {
        return 'renewals';
    }

    protected function buildMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your membership is confirmed')
            ->greeting("Hi {$notifiable->first_name},")
            ->line("Your **{$this->membership->plan->name}** membership runs from ".format_date($this->membership->starts_on).' to '.format_date($this->membership->ends_on).'.')
            ->line('See you at the gym!');
    }

    protected function buildArray(object $notifiable): array
    {
        return [
            'title' => 'Membership confirmed',
            'body' => "{$this->membership->plan->name}: ".format_date($this->membership->starts_on).' – '.format_date($this->membership->ends_on),
            'icon' => 'check-circle',
        ];
    }
}
