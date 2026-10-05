{{-- Invoice body shared by the on-screen view and the print view. --}}
<div class="text-zinc-900">
    <div class="flex flex-wrap items-start justify-between gap-6">
        <div class="flex items-center gap-3">
            @if ($tenant->logo_url)
                <img src="{{ $tenant->logo_url }}" alt="{{ $tenant->name }}" class="size-14 rounded-xl object-contain">
            @else
                <span class="grid size-14 place-items-center rounded-xl text-lg font-bold text-white" style="background: {{ $tenant->primary_color }}">{{ $tenant->initials }}</span>
            @endif
            <div class="text-sm">
                <p class="text-base font-semibold">{{ $tenant->name }}</p>
                <p class="text-zinc-500">{{ collect([$tenant->address_line, $tenant->city, $tenant->postal_code, $tenant->country])->filter()->join(', ') }}</p>
                <p class="text-zinc-500">{{ collect([$tenant->phone, $tenant->email])->filter()->join(' · ') }}</p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-2xl font-semibold tracking-tight" style="color: {{ $tenant->primary_color }}">INVOICE</p>
            <p class="font-mono text-sm">{{ $invoice->number }}</p>
            <div class="mt-2"><x-status-badge :status="$invoice->status" /></div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 text-sm sm:grid-cols-3">
        <div><p class="text-xs font-semibold tracking-wider text-zinc-400 uppercase">Bill to</p><p class="mt-1 font-medium">{{ $invoice->member->full_name }}</p><p class="text-zinc-500">{{ $invoice->member->member_code }}</p><p class="text-zinc-500">{{ $invoice->member->email }}</p></div>
        <div><p class="text-xs font-semibold tracking-wider text-zinc-400 uppercase">Issued</p><p class="mt-1 font-medium">{{ format_date($invoice->issued_on) }}</p></div>
        <div><p class="text-xs font-semibold tracking-wider text-zinc-400 uppercase">Due</p><p class="mt-1 font-medium">{{ format_date($invoice->due_on) }}</p></div>
    </div>

    <div class="mt-8 overflow-x-auto"><table class="w-full text-sm">
        <thead><tr class="border-b-2 border-zinc-900 text-left text-xs tracking-wider text-zinc-500 uppercase"><th class="py-2">Description</th><th class="py-2 pl-4 text-right whitespace-nowrap">Qty</th><th class="py-2 pl-4 text-right whitespace-nowrap">Price</th><th class="py-2 pl-4 text-right whitespace-nowrap">Discount</th><th class="py-2 pl-4 text-right whitespace-nowrap">Tax</th><th class="py-2 pl-4 text-right whitespace-nowrap">Amount</th></tr></thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr class="border-b border-zinc-100">
                    <td class="py-3 pr-4">{{ $item->description }}</td>
                    <td class="py-3 pl-4 text-right whitespace-nowrap tabular-nums">{{ rtrim(rtrim($item->quantity, '0'), '.') }}</td>
                    <td class="py-3 pl-4 text-right whitespace-nowrap tabular-nums">{{ money($item->unit_price, $invoice->currency) }}</td>
                    <td class="py-3 pl-4 text-right whitespace-nowrap tabular-nums">{{ (float) $item->discount ? '-'.money($item->discount, $invoice->currency) : '—' }}</td>
                    <td class="py-3 pl-4 text-right whitespace-nowrap tabular-nums">{{ (float) $item->tax_amount ? money($item->tax_amount, $invoice->currency) : '—' }}</td>
                    <td class="py-3 pl-4 text-right font-medium whitespace-nowrap tabular-nums">{{ money($item->line_total, $invoice->currency) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table></div>

    <div class="mt-6 flex justify-end">
        <dl class="w-full max-w-xs space-y-1.5 text-sm">
            <div class="flex justify-between"><dt class="text-zinc-500">Subtotal</dt><dd class="tabular-nums">{{ money($invoice->subtotal, $invoice->currency) }}</dd></div>
            @if ((float) $invoice->discount_total)<div class="flex justify-between"><dt class="text-zinc-500">Discounts</dt><dd class="tabular-nums">-{{ money($invoice->discount_total, $invoice->currency) }}</dd></div>@endif
            @if ((float) $invoice->tax_total)<div class="flex justify-between"><dt class="text-zinc-500">{{ $tenant->setting('tax.label', 'Tax') }}</dt><dd class="tabular-nums">{{ money($invoice->tax_total, $invoice->currency) }}</dd></div>@endif
            <div class="flex justify-between border-t border-zinc-200 pt-1.5 text-base font-semibold"><dt>Total</dt><dd class="tabular-nums">{{ money($invoice->total, $invoice->currency) }}</dd></div>
            <div class="flex justify-between"><dt class="text-zinc-500">Paid</dt><dd class="tabular-nums">{{ money($invoice->amount_paid, $invoice->currency) }}</dd></div>
            <div class="flex justify-between font-semibold"><dt>Balance due</dt><dd class="tabular-nums">{{ money($invoice->status === \App\Enums\InvoiceStatus::Void ? 0 : $invoice->balance(), $invoice->currency) }}</dd></div>
        </dl>
    </div>

    @if ($invoice->notes)<p class="mt-8 text-sm whitespace-pre-line text-zinc-600">{{ $invoice->notes }}</p>@endif
    @if ($tenant->setting('invoice.footer'))<p class="mt-8 border-t border-zinc-100 pt-4 text-center text-xs text-zinc-500">{{ $tenant->setting('invoice.footer') }}</p>@endif
</div>
