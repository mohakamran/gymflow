@props(['status'])
{{-- Badge for any enum that implements label() and color(). --}}
@if ($status)
    <x-badge :color="$status->color()">{{ $status->label() }}</x-badge>
@endif
