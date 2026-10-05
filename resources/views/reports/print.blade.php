<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $report['title']])
    <style>
        table.report { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 16px; }
        table.report th { text-align: left; font-size: 10px; text-transform: uppercase; color: #71717a; border-bottom: 2px solid #18181b; padding: 6px; }
        table.report td { border-bottom: 1px solid #e4e4e7; padding: 6px; }
        table.report tfoot td { font-weight: 600; border-top: 2px solid #18181b; }
        .right { text-align: right; font-variant-numeric: tabular-nums; }
        @media print { .no-print { display: none; } @page { margin: 14mm; } }
    </style>
</head>
<body class="bg-white font-sans text-zinc-900" onload="setTimeout(() => window.print(), 300)">
    <script>document.documentElement.classList.remove('dark');</script>
    <div class="mx-auto max-w-5xl p-8">
        <div class="flex items-start justify-between">
            <div><h1 class="text-2xl font-semibold">{{ $report['title'] }}</h1><p class="text-sm text-zinc-500">{{ $tenant->name }} · {{ $report['period']->label() }}</p></div>
            <button onclick="window.print()" class="no-print rounded-lg bg-zinc-900 px-3 py-1.5 text-sm font-semibold text-white">Print</button>
        </div>
        @if ($report['summary'])
            <div class="mt-6 flex flex-wrap gap-8">@foreach ($report['summary'] as [$label, $value])<div><p class="text-xs text-zinc-500">{{ $label }}</p><p class="text-lg font-semibold">{{ $value }}</p></div>@endforeach</div>
        @endif
        @include('reports._table')
    </div>
</body>
</html>
