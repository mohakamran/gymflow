<x-layouts.app title="Leads">
    <x-page-header title="Leads" description="Enquiries from your public gym page." />
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('enquiries.index') }}" @class(['rounded-full px-3.5 py-1.5 text-sm font-medium', 'bg-brand-600 text-white' => ! $status, 'bg-white ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800' => $status])>All</a>
        @foreach (\App\Models\Enquiry::STATUSES as $value => $label)
            <a href="{{ route('enquiries.index', ['status' => $value]) }}" @class(['rounded-full px-3.5 py-1.5 text-sm font-medium', 'bg-brand-600 text-white' => $status === $value, 'bg-white ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800' => $status !== $value])>{{ $label }} <span class="text-xs opacity-70">{{ $counts[$value] ?? 0 }}</span></a>
        @endforeach
    </div>
    <x-card :padding="false">
        @if ($enquiries->isEmpty())
            <x-empty-state icon="mail" title="No leads yet" description="Enable your public gym page in Settings → Gym profile to start collecting enquiries." />
        @else
            <div class="overflow-x-auto">
                <table class="table-default">
                    <thead><tr><th>Received</th><th>Name</th><th>Contact</th><th>Interest</th><th class="hidden lg:table-cell">Message</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($enquiries as $enquiry)
                            <tr>
                                <td class="whitespace-nowrap text-xs">{{ $enquiry->created_at->diffForHumans() }}</td>
                                <td class="font-medium">{{ $enquiry->name }}</td>
                                <td class="text-xs">@if ($enquiry->email)<a href="mailto:{{ $enquiry->email }}" class="hover:text-brand-600">{{ $enquiry->email }}</a><br>@endif{{ $enquiry->phone }}</td>
                                <td>{{ $enquiry->interest ?? '—' }}</td>
                                <td class="hidden max-w-xs truncate lg:table-cell" title="{{ $enquiry->message }}">{{ $enquiry->message ?? '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('enquiries.update', $enquiry) }}">@csrf @method('PATCH')
                                        <select name="status" class="form-control w-auto py-1 text-xs" onchange="this.form.submit()" aria-label="Status">
                                            @foreach (\App\Models\Enquiry::STATUSES as $value => $label)<option value="{{ $value }}" @selected($enquiry->status === $value)>{{ $label }}</option>@endforeach
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $enquiries->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
