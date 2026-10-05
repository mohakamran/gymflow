@php
    $tenant = $currentTenant ?? null;
    $brand = $tenant && preg_match('/^#[0-9a-fA-F]{6}$/', $tenant->primary_color) ? $tenant->primary_color : '#4f46e5';
    $appTitle = $tenant?->name ?? config('app.name');
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ isset($title) && $title ? $title.' · '.$appTitle : $appTitle }}</title>
@if ($tenant?->favicon_url)
    <link rel="icon" href="{{ $tenant->favicon_url }}">
@else
    <link rel="icon" href="data:image/svg+xml,{{ rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="8" fill="'.$brand.'"/><path d="M9 13v6M12 11v10M20 11v10M23 13v6M12 16h8" stroke="white" stroke-width="2.4" stroke-linecap="round"/></svg>') }}">
@endif
<style>:root { --brand: {{ $brand }}; }</style>
<script>
    (() => {
        const mode = localStorage.getItem('theme') || 'system';
        const dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
    })();
</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
