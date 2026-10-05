<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Invoice '.$invoice->number])
    <style>@media print { @page { margin: 16mm; } .no-print { display: none !important; } }</style>
</head>
<body class="bg-white font-sans" onload="setTimeout(() => window.print(), 300)">
    <script>document.documentElement.classList.remove('dark');</script>
    <div class="mx-auto max-w-3xl p-8">
        <div class="no-print mb-6 flex justify-end gap-2"><button onclick="window.print()" class="rounded-lg bg-zinc-900 px-3 py-1.5 text-sm font-semibold text-white">Print</button></div>
        @include('invoices._document')
    </div>
</body>
</html>
