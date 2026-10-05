@props(['config', 'height' => 'h-64', 'title' => null])
{{--
    Chart with an accessible table view. $config: [type, labels, series => [[label, data]], format]
--}}
@php
    $config['symbol'] = config('gym.currencies.'.(tenant()?->currency ?? 'USD').'.0', '$');
    $hasData = collect($config['series'] ?? [])->flatMap(fn ($series) => $series['data'])->filter(fn ($value) => (float) $value != 0)->isNotEmpty();
@endphp
<div x-data="{ table: false }" {{ $attributes }}>
    @if ($hasData)
        <div x-show="!table" class="relative {{ $height }}">
            <canvas x-data="chart(@js($config))" role="img" aria-label="{{ $title ?? 'Chart' }}"></canvas>
        </div>
        <div x-show="table" x-cloak class="max-h-72 overflow-auto">
            <table class="table-default">
                <thead><tr><th>{{ $config['labelHeading'] ?? 'Label' }}</th>@foreach ($config['series'] as $series)<th class="text-right">{{ $series['label'] }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($config['labels'] as $index => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            @foreach ($config['series'] as $series)
                                <td class="text-right tabular-nums">{{ ($config['format'] ?? 'number') === 'money' ? money($series['data'][$index] ?? 0) : number_format((float) ($series['data'][$index] ?? 0), is_float($series['data'][$index] ?? 0) && floor($series['data'][$index]) != $series['data'][$index] ? 1 : 0) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" @click="table = !table" class="mt-2 text-xs font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200" x-text="table ? 'Show chart' : 'View as table'"></button>
    @else
        <div class="grid {{ $height }} place-items-center rounded-xl border border-dashed border-zinc-200 text-sm text-zinc-400 dark:border-zinc-800">No data for this period yet</div>
    @endif
</div>
