<?php

namespace App\Http\Controllers\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\InvoiceRequest;
use App\Models\Invoice;
use App\Models\Member;
use App\Notifications\InvoiceIssuedNotification;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $invoices) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);

        $filters = $request->validate([
            'status' => ['nullable', Rule::in([...InvoiceStatus::values(), 'overdue'])],
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $invoices = Invoice::query()
            ->with('member')
            ->when($filters['status'] ?? null, fn ($query, $status) => $status === 'overdue' ? $query->overdue() : $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($inner) => $inner
                ->where('number', 'like', "%{$search}%")
                ->orWhereHas('member', fn ($member) => $member->search($search))))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('issued_on', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('issued_on', '<=', $to))
            ->latest('issued_on')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $open = Invoice::query()->open()->get(['total', 'amount_paid', 'due_on', 'status']);

        return view('invoices.index', [
            'invoices' => $invoices,
            'filters' => $filters,
            'summary' => [
                'outstanding' => $open->sum(fn ($invoice) => $invoice->balance()),
                'overdue' => $open->filter->isOverdue()->sum(fn ($invoice) => $invoice->balance()),
                'overdueCount' => $open->filter->isOverdue()->count(),
                'paidThisMonth' => Invoice::query()->where('status', InvoiceStatus::Paid->value)->where('issued_on', '>=', tenant_today()->startOfMonth()->toDateString())->sum('total'),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Invoice::class);

        return view('invoices.create', [
            'member' => $request->integer('member') ? Member::find($request->integer('member')) : null,
            'taxEnabled' => (bool) tenant()->setting('tax.enabled'),
            'taxRate' => (float) tenant()->setting('tax.rate', 0),
        ]);
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->invoices->create(tenant(), Member::findOrFail($request->integer('member_id')), $request->validated('items'), $request->safe()->only(['issued_on', 'due_on', 'notes']));

        return $this->done(redirect()->route('invoices.show', $invoice), "Invoice {$invoice->number} created.");
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        return view('invoices.show', [
            'invoice' => $invoice->load(['items', 'member', 'payments.receiver', 'membership.plan']),
            'tenant' => tenant(),
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function print(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        return view('invoices.print', ['invoice' => $invoice->load(['items', 'member', 'payments']), 'tenant' => tenant()]);
    }

    public function pdf(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        return $this->invoices->pdf($invoice)->download($this->invoices->filename($invoice));
    }

    public function email(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);
        $invoice->loadMissing('member');

        if (! $invoice->member->email) {
            throw new BusinessRuleException('This member has no email address.');
        }

        $invoice->member->notify(new InvoiceIssuedNotification(tenant(), $invoice));
        $invoice->forceFill(['sent_at' => now()])->save();

        return $this->done(back(), "Invoice emailed to {$invoice->member->email}.");
    }

    public function void(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);
        $this->invoices->void($invoice);

        return $this->done(back(), "Invoice {$invoice->number} voided.");
    }
}
