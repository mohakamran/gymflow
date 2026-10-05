<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentDueNotification extends GymNotification
{
    public function __construct(Tenant $tenant, public Invoice $invoice)
    {
        parent::__construct($tenant);
    }

    public static function preferenceKey(): ?string
    {
        return 'payment_due';
    }

    protected function buildMail(object $notifiable): MailMessage
    {
        $balance = money($this->invoice->balance(), $this->invoice->currency);

        return (new MailMessage)
            ->subject("Payment reminder: invoice {$this->invoice->number}")
            ->greeting("Hi {$notifiable->first_name},")
            ->line("This is a friendly reminder that **{$balance}** is outstanding on invoice {$this->invoice->number}, due ".format_date($this->invoice->due_on).'.')
            ->line('You can pay at the front desk on your next visit.');
    }

    protected function buildArray(object $notifiable): array
    {
        return [
            'title' => 'Payment due',
            'body' => money($this->invoice->balance(), $this->invoice->currency)." outstanding on invoice {$this->invoice->number}.",
            'icon' => 'exclamation',
        ];
    }

    protected function buildText(object $notifiable): ?string
    {
        return money($this->invoice->balance(), $this->invoice->currency)." is due on invoice {$this->invoice->number}. Please pay at the front desk.";
    }
}
