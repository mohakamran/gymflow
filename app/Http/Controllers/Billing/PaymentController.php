<?php

namespace App\Http\Controllers\Billing;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\PaymentRequest;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Services\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $payments) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $filters = $request->validate([
            'method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $from = $filters['from'] ?? tenant_today()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? tenant_today()->toDateString();
        $range = [CarbonImmutable::parse($from, tenant_timezone())->startOfDay()->utc(), CarbonImmutable::parse($to, tenant_timezone())->endOfDay()->utc()];

        $query = Payment::query()
            ->whereBetween('paid_at', $range)
            ->when($filters['method'] ?? null, fn ($query, $method) => $query->where('method', $method))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($inner) => $inner
                ->whereLike('reference', "%{$search}%")
                ->orWhereHas('member', fn ($member) => $member->search($search))));

        $successful = (clone $query)->successful()->get(['amount', 'refunded_amount', 'method']);

        return view('payments.index', [
            'payments' => $query->with(['member', 'invoice', 'receiver'])->latest('paid_at')->paginate(25)->withQueryString(),
            'filters' => $filters + ['from' => $from, 'to' => $to],
            'total' => $successful->sum(fn ($payment) => $payment->netAmount()),
            'byMethod' => $successful->groupBy(fn ($payment) => $payment->method->label())->map(fn ($group) => $group->sum(fn ($payment) => $payment->netAmount()))->sortDesc(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Payment::class);

        $invoice = $request->integer('invoice') ? Invoice::with('member')->find($request->integer('invoice')) : null;
        $member = $invoice?->member ?? ($request->integer('member') ? Member::find($request->integer('member')) : null);

        return view('payments.create', [
            'invoice' => $invoice,
            'member' => $member,
            'openInvoices' => $member ? $member->invoices()->open()->latest('issued_on')->get() : collect(),
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function store(PaymentRequest $request): RedirectResponse
    {
        $member = Member::withTrashed()->findOrFail($request->integer('member_id'));
        $invoice = $request->filled('invoice_id') ? Invoice::findOrFail($request->integer('invoice_id')) : null;

        $payment = $this->payments->record($member, (float) $request->input('amount'), PaymentMethod::from($request->input('method')), $invoice, [
            'reference' => $request->input('reference'),
            'notes' => $request->input('notes'),
            'paid_at' => $request->input('paid_at') ? CarbonImmutable::parse($request->input('paid_at'), tenant_timezone())->utc() : null,
        ]);

        return $this->done(
            $invoice ? redirect()->route('invoices.show', $invoice) : redirect()->route('payments.show', $payment),
            money($payment->amount).' received from '.$member->full_name.'.',
        );
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        return view('payments.show', ['payment' => $payment->load(['member', 'invoice', 'receiver'])]);
    }

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->payments->refund($payment, (float) $data['amount'], $data['reason'] ?? null);

        return $this->done(back(), money($data['amount']).' refunded.');
    }
}
