<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\InvoiceService;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Emails an invoice with the PDF attached (triggered by staff from the invoice page).
 */
class InvoiceIssuedNotification extends GymNotification
{
    public function __construct(Tenant $tenant, public Invoice $invoice)
    {
        parent::__construct($tenant);
    }

    public static function preferenceKey(): ?string
    {
        return null;
    }

    protected function buildMail(object $notifiable): MailMessage
    {
        $service = app(InvoiceService::class);
        $total = money($this->invoice->total, $this->invoice->currency);

        return (new MailMessage)
            ->subject("Invoice {$this->invoice->number} from {$this->tenant->name}")
            ->greeting("Hi {$notifiable->first_name},")
            ->line("Please find attached invoice **{$this->invoice->number}** for **{$total}**.")
            ->line($this->invoice->status->isOpen()
                ? 'Balance due: '.money($this->invoice->balance(), $this->invoice->currency).' by '.format_date($this->invoice->due_on).'.'
                : 'This invoice is marked as '.strtolower($this->invoice->status->label()).'.')
            ->attachData($service->pdf($this->invoice)->output(), $service->filename($this->invoice), ['mime' => 'application/pdf']);
    }

    protected function buildArray(object $notifiable): array
    {
        return [
            'title' => "Invoice {$this->invoice->number}",
            'body' => 'A new invoice for '.money($this->invoice->total, $this->invoice->currency).' is available.',
            'icon' => 'clipboard',
        ];
    }
}
