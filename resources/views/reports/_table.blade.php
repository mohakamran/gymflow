<table class="report">
    <thead><tr>@foreach ($report['columns'] as $key => $label)<th class="{{ isset($report['formats'][$key]) ? 'right' : '' }}">{{ $label }}</th>@endforeach</tr></thead>
    <tbody>
        @foreach ($report['rows'] as $row)
            <tr>@foreach ($report['columns'] as $key => $label)<td class="{{ isset($report['formats'][$key]) ? 'right' : '' }}">{{ \App\Services\Reporting\ReportService::cell($row[$key] ?? null, $report['formats'][$key] ?? null) }}</td>@endforeach</tr>
        @endforeach
    </tbody>
    @if ($report['totals'])
        <tfoot><tr>@foreach ($report['columns'] as $key => $label)<td class="{{ isset($report['formats'][$key]) ? 'right' : '' }}">{{ \App\Services\Reporting\ReportService::cell($report['totals'][$key] ?? '', $report['formats'][$key] ?? null) }}</td>@endforeach</tr></tfoot>
    @endif
</table>
