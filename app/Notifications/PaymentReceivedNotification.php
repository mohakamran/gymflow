<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentReceivedNotification extends GymNotification
{
    public function __construct(Tenant $tenant, public Payment $payment)
    {
        parent::__construct($tenant);
    }

    public static function preferenceKey(): ?string
    {
        return 'payment_receipts';
    }

    protected function buildMail(object $notifiable): MailMessage
    {
        $amount = money($this->payment->amount, $this->tenant->currency);
        $mail = (new MailMessage)
            ->subject("Payment received — {$amount}")
            ->greeting("Thanks, {$notifiable->first_name}!")
            ->line("We've received your payment of **{$amount}** on ".format_date($this->payment->paid_at).'.')
            ->line("Reference: {$this->payment->reference}");

        if ($this->payment->invoice) {
            $mail->line("Applied to invoice {$this->payment->invoice->number}. Remaining balance: ".money($this->payment->invoice->balance(), $this->tenant->currency).'.');
        }

        return $mail;
    }

    protected function buildArray(object $notifiable): array
    {
        return [
            'title' => 'Payment received',
            'body' => money($this->payment->amount, $this->tenant->currency).' received (ref '.$this->payment->reference.').',
            'icon' => 'card',
        ];
    }

    protected function buildText(object $notifiable): ?string
    {
        return 'Payment of '.money($this->payment->amount, $this->tenant->currency).' received. Ref '.$this->payment->reference.'. Thank you!';
    }
}
