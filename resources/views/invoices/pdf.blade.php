<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoice {{ $invoice->number }}</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #18181b; margin: 0; }
    .brand { color: {{ preg_match('/^#[0-9a-fA-F]{6}$/', $tenant->primary_color) ? $tenant->primary_color : '#4f46e5' }}; }
    .muted { color: #71717a; }
    .label { font-size: 9px; letter-spacing: .08em; text-transform: uppercase; color: #a1a1aa; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; }
    .items th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .06em; color: #71717a; border-bottom: 2px solid #18181b; padding: 6px 4px; }
    .items td { border-bottom: 1px solid #e4e4e7; padding: 8px 4px; vertical-align: top; }
    .right { text-align: right; }
    .totals td { padding: 3px 4px; }
    .total-row td { border-top: 1px solid #d4d4d8; font-size: 13px; font-weight: bold; padding-top: 6px; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; background: #f4f4f5; font-size: 10px; font-weight: bold; }
    .monogram { display: inline-block; width: 44px; height: 44px; line-height: 44px; text-align: center; border-radius: 10px; color: #fff; font-weight: bold; font-size: 16px; }
</style>
</head>
<body>
    <table>
        <tr>
            <td style="width: 60%; vertical-align: top;">
                @php $logo = $tenant->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($tenant->logo_path) ? \Illuminate\Support\Facades\Storage::disk('public')->path($tenant->logo_path) : null; @endphp
                @if ($logo)
                    <img src="{{ $logo }}" style="height: 48px; max-width: 160px;" alt="">
                @else
                    <span class="monogram" style="background: {{ preg_match('/^#[0-9a-fA-F]{6}$/', $tenant->primary_color) ? $tenant->primary_color : '#4f46e5' }}">{{ $tenant->initials }}</span>
                @endif
                <p style="font-size: 14px; font-weight: bold; margin: 8px 0 2px;">{{ $tenant->name }}</p>
                <p class="muted" style="margin: 0;">{{ collect([$tenant->address_line, $tenant->city, $tenant->postal_code, $tenant->country])->filter()->join(', ') }}</p>
                <p class="muted" style="margin: 0;">{{ collect([$tenant->phone, $tenant->email, $tenant->website])->filter()->join(' · ') }}</p>
            </td>
            <td class="right" style="vertical-align: top;">
                <p class="brand" style="font-size: 24px; font-weight: bold; margin: 0;">INVOICE</p>
                <p style="font-family: monospace; font-size: 12px; margin: 2px 0 8px;">{{ $invoice->number }}</p>
                <span class="badge">{{ $invoice->status->label() }}</span>
            </td>
        </tr>
    </table>

    <table style="margin-top: 28px;">
        <tr>
            <td style="vertical-align: top; width: 40%;"><div class="label">Bill to</div><p style="margin: 4px 0 0; font-weight: bold;">{{ $invoice->member->full_name }}</p><p class="muted" style="margin: 0;">{{ $invoice->member->member_code }}</p><p class="muted" style="margin: 0;">{{ $invoice->member->email }}</p></td>
            <td style="vertical-align: top;"><div class="label">Issued</div><p style="margin: 4px 0 0;">{{ format_date($invoice->issued_on) }}</p></td>
            <td style="vertical-align: top;"><div class="label">Due</div><p style="margin: 4px 0 0;">{{ format_date($invoice->due_on) }}</p></td>
        </tr>
    </table>

    <table class="items" style="margin-top: 24px;">
        <thead><tr><th>Description</th><th class="right">Qty</th><th class="right">Price</th><th class="right">Discount</th><th class="right">Tax</th><th class="right">Amount</th></tr></thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ rtrim(rtrim($item->quantity, '0'), '.') }}</td>
                    <td class="right">{{ money($item->unit_price, $invoice->currency) }}</td>
                    <td class="right">{{ (float) $item->discount ? '-'.money($item->discount, $invoice->currency) : '—' }}</td>
                    <td class="right">{{ (float) $item->tax_amount ? money($item->tax_amount, $invoice->currency) : '—' }}</td>
                    <td class="right"><strong>{{ money($item->line_total, $invoice->currency) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 16px;">
        <tr>
            <td style="width: 55%;"></td>
            <td>
                <table class="totals">
                    <tr><td class="muted">Subtotal</td><td class="right">{{ money($invoice->subtotal, $invoice->currency) }}</td></tr>
                    @if ((float) $invoice->discount_total)<tr><td class="muted">Discounts</td><td class="right">-{{ money($invoice->discount_total, $invoice->currency) }}</td></tr>@endif
                    @if ((float) $invoice->tax_total)<tr><td class="muted">{{ $tenant->setting('tax.label', 'Tax') }}</td><td class="right">{{ money($invoice->tax_total, $invoice->currency) }}</td></tr>@endif
                    <tr class="total-row"><td>Total</td><td class="right">{{ money($invoice->total, $invoice->currency) }}</td></tr>
                    <tr><td class="muted">Paid</td><td class="right">{{ money($invoice->amount_paid, $invoice->currency) }}</td></tr>
                    <tr><td><strong>Balance due</strong></td><td class="right"><strong>{{ money($invoice->status === \App\Enums\InvoiceStatus::Void ? 0 : $invoice->balance(), $invoice->currency) }}</strong></td></tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($invoice->payments->isNotEmpty())
        <div class="label" style="margin-top: 24px;">Payments</div>
        @foreach ($invoice->payments as $payment)
            <p class="muted" style="margin: 3px 0;">{{ format_date($payment->paid_at) }} · {{ $payment->method->label() }} · {{ $payment->reference }} · {{ money($payment->amount, $invoice->currency) }}@if ((float) $payment->refunded_amount) (refunded {{ money($payment->refunded_amount, $invoice->currency) }})@endif</p>
        @endforeach
    @endif

    @if ($invoice->notes)<p style="margin-top: 24px;">{{ $invoice->notes }}</p>@endif
    @if ($tenant->setting('invoice.footer'))<p class="muted" style="margin-top: 32px; text-align: center; border-top: 1px solid #e4e4e7; padding-top: 10px;">{{ $tenant->setting('invoice.footer') }}</p>@endif
</body>
</html>
