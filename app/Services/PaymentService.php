<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Tenant;
use App\Notifications\PaymentReceivedNotification;
use App\Payments\PaymentGatewayManager;
use App\Payments\PaymentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        protected PaymentGatewayManager $gateways,
        protected InvoiceService $invoices,
    ) {}

    /**
     * Record a payment against an invoice (or as a standalone payment).
     *
     * @param  array{reference?: string|null, notes?: string|null, paid_at?: string|null, gateway?: string, notify?: bool}  $options
     */
    public function record(Member $member, float $amount, PaymentMethod $method, ?Invoice $invoice = null, array $options = []): Payment
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new BusinessRuleException('The payment amount must be greater than zero.');
        }

        if ($invoice !== null) {
            if ($invoice->member_id !== $member->id) {
                throw new BusinessRuleException('This invoice belongs to a different member.');
            }

            if (! $invoice->status->isOpen()) {
                throw new BusinessRuleException("Invoice {$invoice->number} is {$invoice->status->label()} and cannot take payments.");
            }

            if ($amount > $invoice->balance()) {
                throw new BusinessRuleException('The amount is more than the invoice balance of '.money($invoice->balance(), $invoice->currency).'.');
            }
        }

        $tenant = Tenant::findOrFail($member->tenant_id);
        $gateway = $this->gateways->gateway($options['gateway'] ?? 'manual');
        $result = $gateway->charge(new PaymentRequest($member, $amount, $tenant->currency, $method, $invoice, $options['reference'] ?? null));

        if (! $result->successful && ! $result->pending) {
            throw new BusinessRuleException($result->message ?? 'The payment could not be processed.');
        }

        $payment = DB::transaction(function () use ($member, $amount, $method, $invoice, $options, $gateway, $result): Payment {
            $payment = new Payment([
                'member_id' => $member->id,
                'invoice_id' => $invoice?->id,
                'reference' => $this->newReference(),
                'amount' => $amount,
                'method' => $method,
                'status' => $result->pending ? PaymentStatus::Pending : PaymentStatus::Completed,
                'gateway' => $gateway->key(),
                'gateway_reference' => $result->gatewayReference ?? ($options['reference'] ?? null),
                'paid_at' => $options['paid_at'] ?? now(),
                'received_by' => auth()->id(),
                'notes' => $options['notes'] ?? null,
            ]);
            $payment->tenant_id = $member->tenant_id;
            $payment->save();

            if ($invoice !== null) {
                $this->invoices->syncPayments($invoice);
            }

            return $payment;
        });

        if (($options['notify'] ?? true) && $payment->status === PaymentStatus::Completed && $member->email) {
            $member->notify(new PaymentReceivedNotification($tenant, $payment));
        }

        return $payment;
    }

    public function refund(Payment $payment, float $amount, ?string $reason = null): Payment
    {
        $amount = round($amount, 2);

        if ($amount <= 0 || $amount > $payment->refundableAmount()) {
            throw new BusinessRuleException('Refund must be between 0 and '.money($payment->refundableAmount()).'.');
        }

        $result = $this->gateways->gateway($payment->gateway)->refund($payment, $amount);

        if (! $result->successful) {
            throw new BusinessRuleException($result->message ?? 'The refund could not be processed.');
        }

        return DB::transaction(function () use ($payment, $amount, $reason): Payment {
            $payment->refunded_amount = round((float) $payment->refunded_amount + $amount, 2);
            $payment->status = $payment->refundableAmount() <= 0 ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded;
            $payment->notes = trim(($payment->notes ? $payment->notes."\n" : '').'Refunded '.money($amount).($reason ? ": $reason" : ''));
            $payment->save();

            if ($payment->invoice) {
                $this->invoices->syncPayments($payment->invoice);
            }

            return $payment;
        });
    }

    protected function newReference(): string
    {
        return 'PAY-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
    }
}
