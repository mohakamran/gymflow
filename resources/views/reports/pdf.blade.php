<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $report['title'] }}</title>
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #18181b; }
    h1 { font-size: 18px; margin: 0; }
    .muted { color: #71717a; }
    .summary td { padding: 6px 10px 6px 0; }
    .summary .value { font-size: 13px; font-weight: bold; }
    table.report { width: 100%; border-collapse: collapse; margin-top: 14px; }
    table.report th { text-align: left; font-size: 8.5px; text-transform: uppercase; letter-spacing: .05em; color: #71717a; border-bottom: 1.5px solid #18181b; padding: 5px 4px; }
    table.report td { border-bottom: 1px solid #e4e4e7; padding: 5px 4px; }
    table.report tfoot td { font-weight: bold; border-top: 1.5px solid #18181b; border-bottom: 0; }
    .right { text-align: right; }
</style>
</head>
<body>
    <table style="width: 100%;"><tr>
        <td><h1>{{ $report['title'] }}</h1><p class="muted" style="margin: 2px 0 0;">{{ $tenant->name }} · {{ $report['period']->label() }}</p></td>
        <td class="right muted">Generated {{ tenant_now()->format('M j, Y g:i A') }}</td>
    </tr></table>
    @if ($report['summary'])
        <table class="summary" style="margin-top: 12px;"><tr>
            @foreach ($report['summary'] as [$label, $value])<td><div class="muted">{{ $label }}</div><div class="value">{{ $value }}</div></td>@endforeach
        </tr></table>
    @endif
    @include('reports._table')
</body>
</html>
