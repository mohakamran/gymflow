<?php

namespace App\Http\Controllers\Billing;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\ExpenseRequest;
use App\Models\Expense;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);

        $filters = $request->validate([
            'category' => ['nullable', Rule::enum(ExpenseCategory::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $from = $filters['from'] ?? tenant_today()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? tenant_today()->toDateString();

        $query = Expense::query()
            ->whereBetween('spent_on', [$from, $to])
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($inner) => $inner->where('title', 'like', "%{$search}%")->orWhere('vendor', 'like', "%{$search}%")));

        $expenseTotal = (float) (clone $query)->sum('amount');
        $range = [CarbonImmutable::parse($from, tenant_timezone())->startOfDay()->utc(), CarbonImmutable::parse($to, tenant_timezone())->endOfDay()->utc()];
        $revenue = Payment::query()->successful()->whereBetween('paid_at', $range)->get(['amount', 'refunded_amount'])->sum(fn ($payment) => $payment->netAmount());

        return view('expenses.index', [
            'expenses' => (clone $query)->with('recorder')->latest('spent_on')->latest('id')->paginate(25)->withQueryString(),
            'filters' => $filters + ['from' => $from, 'to' => $to],
            'total' => $expenseTotal,
            'revenue' => $revenue,
            'byCategory' => (clone $query)->toBase()->selectRaw('category, sum(amount) as total')->groupBy('category')->orderByDesc('total')->pluck('total', 'category'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Expense::class);

        return view('expenses.form', ['expense' => new Expense(['spent_on' => tenant_today()]), 'methods' => PaymentMethod::options()]);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $expense = new Expense($request->safe()->except('receipt'));
        $expense->recorded_by = $request->user()->id;
        $expense->save();
        $this->storeReceipt($request, $expense);

        return $this->done(redirect()->route('expenses.index'), 'Expense recorded.');
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);

        return view('expenses.form', ['expense' => $expense, 'methods' => PaymentMethod::options()]);
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);
        $expense->update($request->safe()->except('receipt'));
        $this->storeReceipt($request, $expense);

        return $this->done(redirect()->route('expenses.index'), 'Expense updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);
        $expense->delete();

        return $this->done(redirect()->route('expenses.index'), 'Expense deleted.');
    }

    public function receipt(Expense $expense): StreamedResponse
    {
        $this->authorize('view', $expense);
        abort_unless($expense->receipt_path && Storage::disk('local')->exists($expense->receipt_path), 404);

        return Storage::disk('local')->download($expense->receipt_path, 'receipt-'.$expense->id.'.'.pathinfo($expense->receipt_path, PATHINFO_EXTENSION));
    }

    protected function storeReceipt(ExpenseRequest $request, Expense $expense): void
    {
        if (! $request->hasFile('receipt')) {
            return;
        }

        if ($expense->receipt_path) {
            Storage::disk('local')->delete($expense->receipt_path);
        }

        $expense->forceFill(['receipt_path' => $request->file('receipt')->store('tenants/'.$expense->tenant_id.'/receipts', 'local')])->save();
    }
}
