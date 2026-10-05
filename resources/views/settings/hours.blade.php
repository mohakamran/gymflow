<x-layouts.app title="Business hours">
    <x-page-header title="Settings" description="Manage how your gym appears to members and on invoices." />
    @include('settings._nav')

    <form method="POST" action="{{ route('settings.hours.update') }}">
        @csrf @method('PUT')
        <x-card title="Business hours" :description="'Times are in your gym\'s timezone ('.$tenant->timezone.').'">
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($hours as $day => $slot)
                    @php $closed = (bool) old("hours.$day.closed", $slot['closed']); @endphp
                    <div x-data="{ closed: @js($closed) }" class="flex flex-wrap items-center gap-x-6 gap-y-3 py-3.5 first:pt-0 last:pb-0">
                        <p class="w-28 text-sm font-medium capitalize text-zinc-800 dark:text-zinc-200">{{ $day }}</p>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <input type="hidden" name="hours[{{ $day }}][closed]" :value="closed ? 1 : 0" value="{{ $closed ? 1 : 0 }}">
                            <input type="checkbox" class="form-checkbox" x-model="closed"> Closed
                        </label>
                        <div class="flex items-center gap-2" x-show="!closed">
                            <input type="time" name="hours[{{ $day }}][open]" value="{{ old("hours.$day.open", $slot['open'] ?? '06:00') }}" class="form-control w-32" :disabled="closed" aria-label="{{ ucfirst($day) }} opening time">
                            <span class="text-zinc-400">–</span>
                            <input type="time" name="hours[{{ $day }}][close]" value="{{ old("hours.$day.close", $slot['close'] ?? '22:00') }}" class="form-control w-32" :disabled="closed" aria-label="{{ ucfirst($day) }} closing time">
                        </div>
                        @if ($errors->has("hours.$day.open") || $errors->has("hours.$day.close"))
                            <p class="w-full text-xs font-medium text-rose-600">{{ $errors->first("hours.$day.close") ?: $errors->first("hours.$day.open") }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
            <x-slot:footer><x-button>Save hours</x-button></x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
