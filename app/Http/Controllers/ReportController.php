<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\Reporting\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reports) {}

    public function index(): View
    {
        return view('reports.index', ['types' => ReportService::TYPES]);
    }

    public function show(Request $request, string $type): View
    {
        $report = $this->reports->build($this->type($type), $this->filters($request));

        return view('reports.show', [
            'report' => $report,
            'types' => ReportService::TYPES,
            'filters' => $request->query(),
            'options' => $this->filterOptions(),
        ]);
    }

    public function export(Request $request, string $type, string $format): StreamedResponse|Response|View
    {
        abort_unless(in_array($format, ['csv', 'pdf', 'print'], true), 404);
        $report = $this->reports->build($this->type($type), $this->filters($request));
        $filename = str($report['title'])->slug().'-'.$report['period']->from->toDateString().'-to-'.$report['period']->to->toDateString();

        return match ($format) {
            'csv' => $this->csv($report, $filename.'.csv'),
            'pdf' => Pdf::loadView('reports.pdf', ['report' => $report, 'tenant' => tenant()])->setPaper('a4', count($report['columns']) > 5 ? 'landscape' : 'portrait')->download($filename.'.pdf'),
            'print' => view('reports.print', ['report' => $report, 'tenant' => tenant()]),
        };
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function csv(array $report, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($report): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens accents/currency symbols correctly.
            fputcsv($out, array_values($report['columns']));

            foreach (array_merge($report['rows'], $report['totals'] ? [$report['totals']] : []) as $row) {
                fputcsv($out, array_map(fn ($key) => $this->csvCell($row[$key] ?? ''), array_keys($report['columns'])));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Neutralise spreadsheet formula injection in exported text cells.
     */
    protected function csvCell(mixed $value): string|int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    protected function type(string $type): string
    {
        abort_unless(array_key_exists($type, ReportService::TYPES), 404);

        return $type;
    }

    /**
     * @return array<string, mixed>
     */
    protected function filters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'category' => ['nullable', Rule::enum(ExpenseCategory::class)],
            'plan' => ['nullable', 'integer'],
            'trainer' => ['nullable', 'integer'],
        ]);
    }

    /**
     * @return array<string, array<string|int, string>>
     */
    protected function filterOptions(): array
    {
        return [
            'method' => PaymentMethod::options(),
            'payment_status' => PaymentStatus::options(),
            'category' => ExpenseCategory::options(),
            'plan' => MembershipPlan::withTrashed()->orderBy('name')->pluck('name', 'id')->all(),
            'trainer' => User::query()->role(Role::Trainer->value)->orderBy('name')->pluck('name', 'id')->all(),
        ];
    }
}
