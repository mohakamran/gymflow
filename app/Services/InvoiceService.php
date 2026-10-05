<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(protected SequenceGenerator $sequences) {}

    /**
     * Create an invoice. Tax is applied to taxable lines using the gym's tax settings.
     *
     * @param  list<array{description: string, quantity?: float|int|string, unit_price: float|int|string, discount?: float|int|string|null, taxable?: bool}>  $items
     * @param  array{membership?: Membership|null, issued_on?: string|null, due_on?: string|null, notes?: string|null, created_by?: int|null}  $options
     */
    public function create(Tenant $tenant, Member $member, array $items, array $options = []): Invoice
    {
        if ($items === []) {
            throw new BusinessRuleException('An invoice needs at least one line item.');
        }

        $taxEnabled = (bool) $tenant->setting('tax.enabled', false);
        $taxRate = $taxEnabled ? (float) $tenant->setting('tax.rate', 0) : 0.0;

        return DB::transaction(function () use ($tenant, $member, $items, $options, $taxRate): Invoice {
            $issuedOn = $options['issued_on'] ?? tenant_today()->toDateString();

            $invoice = new Invoice([
                'member_id' => $member->id,
                'membership_id' => ($options['membership'] ?? null)?->id,
                'number' => $this->sequences->nextInvoiceNumber($tenant),
                'issued_on' => $issuedOn,
                'due_on' => $options['due_on'] ?? $issuedOn,
                'currency' => $tenant->currency,
                'status' => InvoiceStatus::Unpaid,
                'notes' => $options['notes'] ?? null,
                'created_by' => $options['created_by'] ?? auth()->id(),
            ]);
            $invoice->tenant_id = $tenant->id;

            $lines = collect($items)->map(function (array $item) use ($taxRate): array {
                $quantity = round((float) ($item['quantity'] ?? 1), 2);
                $unitPrice = round((float) $item['unit_price'], 2);
                $gross = round($quantity * $unitPrice, 2);
                $discount = min($gross, round((float) ($item['discount'] ?? 0), 2));
                $rate = ($item['taxable'] ?? true) ? $taxRate : 0.0;
                $tax = round(($gross - $discount) * $rate / 100, 2);

                return [
                    'description' => $item['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'tax_rate' => $rate,
                    'tax_amount' => $tax,
                    'line_total' => round($gross - $discount + $tax, 2),
                    'gross' => $gross,
                ];
            });

            $invoice->subtotal = $lines->sum('gross');
            $invoice->discount_total = $lines->sum('discount');
            $invoice->tax_total = $lines->sum('tax_amount');
            $invoice->total = $lines->sum('line_total');
            $invoice->amount_paid = 0;

            if ((float) $invoice->total <= 0) {
                $invoice->status = InvoiceStatus::Paid;
            }

            $invoice->save();
            $invoice->items()->createMany($lines->map(fn (array $line): array => collect($line)->except('gross')->all())->all());

            return $invoice->load('items');
        });
    }

    public function createForMembership(Membership $membership, ?string $dueOn = null): Invoice
    {
        $membership->loadMissing('plan', 'member');
        $tenant = Tenant::findOrFail($membership->tenant_id);
        $plan = $membership->plan;

        $items = [[
            'description' => sprintf('%s membership (%s – %s)', $plan->name, format_date($membership->starts_on), format_date($membership->ends_on)),
            'quantity' => 1,
            'unit_price' => (float) $membership->price,
            'discount' => (float) $membership->discount_amount,
            'taxable' => $plan->is_taxable,
        ]];

        if ((float) $plan->signup_fee > 0 && $membership->renewed_from_id === null && $membership->member->memberships()->count() === 1) {
            $items[] = ['description' => 'Joining fee', 'quantity' => 1, 'unit_price' => (float) $plan->signup_fee, 'taxable' => $plan->is_taxable];
        }

        return $this->create($tenant, $membership->member, $items, [
            'membership' => $membership,
            'due_on' => $dueOn ?? $membership->starts_on->toDateString(),
        ]);
    }

    /**
     * Re-derive amount paid and status from the invoice's payments.
     */
    public function syncPayments(Invoice $invoice): Invoice
    {
        if ($invoice->status === InvoiceStatus::Void) {
            return $invoice;
        }

        $payments = $invoice->payments()->whereNot('status', PaymentStatus::Failed->value)->whereNot('status', PaymentStatus::Pending->value)->get();
        $paid = round($payments->sum(fn ($payment) => $payment->netAmount()), 2);
        $refunded = round($payments->sum(fn ($payment) => (float) $payment->refunded_amount), 2);

        $invoice->amount_paid = max(0, $paid);
        $invoice->status = match (true) {
            (float) $invoice->total <= 0 || $paid >= (float) $invoice->total => InvoiceStatus::Paid,
            $paid > 0 => InvoiceStatus::Partial,
            $refunded > 0 => InvoiceStatus::Refunded,
            default => InvoiceStatus::Unpaid,
        };
        $invoice->save();

        return $invoice;
    }

    public function void(Invoice $invoice): Invoice
    {
        if ((float) $invoice->amount_paid > 0) {
            throw new BusinessRuleException('Refund the payments on this invoice before voiding it.');
        }

        $invoice->status = InvoiceStatus::Void;
        $invoice->save();

        return $invoice;
    }

    public function pdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->loadMissing('items', 'member', 'payments');
        $tenant = Tenant::findOrFail($invoice->tenant_id);

        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice, 'tenant' => $tenant])
            ->setPaper('a4')
            ->setOption(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
    }

    public function filename(Invoice $invoice): string
    {
        return 'invoice-'.str($invoice->number)->slug().'.pdf';
    }
}
