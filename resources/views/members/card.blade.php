<x-layouts.app :title="'Member card · '.$member->full_name">
    <x-page-header title="Member card" description="Print it or let the member scan it at the front desk / kiosk.">
        <x-slot:actions>
            <x-button variant="secondary" icon="arrow-left" :href="route('members.show', $member)">Back</x-button>
            <x-button icon="printer" onclick="window.print()" type="button">Print</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="flex justify-center print:block">
        <div class="w-full max-w-sm overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-zinc-200 print:shadow-none dark:ring-zinc-700">
            <div class="flex items-center gap-3 bg-brand-600 px-6 py-5 text-white">
                @if ($currentTenant->logo_url)
                    <img src="{{ $currentTenant->logo_url }}" alt="" class="size-10 rounded-xl bg-white object-contain p-1">
                @else
                    <span class="grid size-10 place-items-center rounded-xl bg-white/20 font-bold">{{ $currentTenant->initials }}</span>
                @endif
                <div><p class="font-semibold">{{ $currentTenant->name }}</p><p class="text-xs text-white/80">Membership card</p></div>
            </div>
            <div class="flex flex-col items-center px-6 py-6 text-center text-zinc-900">
                <div x-data="qrCode(@js($member->checkInPayload()), 220)" class="size-[220px] [&_svg]:size-full" aria-label="Check-in QR code"></div>
                <p class="mt-4 text-xl font-semibold">{{ $member->full_name }}</p>
                <p class="font-mono text-sm tracking-widest text-zinc-500">{{ $member->member_code }}</p>
                @if ($member->currentMembership)
                    <p class="mt-3 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium">{{ $member->currentMembership->plan->name }} · valid until {{ format_date($member->currentMembership->ends_on) }}</p>
                @endif
            </div>
        </div>
    </div>
    <p class="mt-6 text-center text-xs text-zinc-500 print:hidden">The QR code contains a private token, not personal data. Regenerate it by contacting support if a card is lost.</p>
</x-layouts.app>
