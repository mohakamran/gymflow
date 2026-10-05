<?php

namespace App\Notifications;

use App\Models\Membership;
use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;

class MembershipExpiringNotification extends GymNotification
{
    public function __construct(Tenant $tenant, public Membership $membership)
    {
        parent::__construct($tenant);
    }

    public static function preferenceKey(): ?string
    {
        return 'membership_expiry';
    }

    protected function buildMail(object $notifiable): MailMessage
    {
        $days = $this->membership->daysRemaining();

        return (new MailMessage)
            ->subject('Your membership ends '.($days === 0 ? 'today' : "in {$days} days"))
            ->greeting("Hi {$notifiable->first_name},")
            ->line("Your **{$this->membership->plan->name}** membership ends on ".format_date($this->membership->ends_on).'.')
            ->line('Renew at the front desk to keep training without interruption.');
    }

    protected function buildArray(object $notifiable): array
    {
        return [
            'title' => 'Membership ending soon',
            'body' => "Your {$this->membership->plan->name} membership ends on ".format_date($this->membership->ends_on).'.',
            'icon' => 'clock',
        ];
    }

    protected function buildText(object $notifiable): ?string
    {
        return "Your {$this->membership->plan->name} membership ends on ".format_date($this->membership->ends_on).'. Renew to keep training!';
    }
}
