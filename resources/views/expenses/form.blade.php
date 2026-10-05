@php $editing = $expense->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit expense' : 'Record expense'">
    <x-page-header :title="$editing ? 'Edit expense' : 'Record an expense'">
        <x-slot:actions><x-button variant="secondary" icon="arrow-left" :href="route('expenses.index')">Back</x-button></x-slot:actions>
    </x-page-header>
    <form method="POST" action="{{ $editing ? route('expenses.update', $expense) : route('expenses.store') }}" enctype="multipart/form-data" class="max-w-3xl">
        @csrf
        @if ($editing) @method('PUT') @endif
        <x-card>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><x-input name="title" label="Description" :value="$expense->title" placeholder="e.g. October rent" required autofocus /></div>
                <x-select name="category" label="Category" :options="\App\Enums\ExpenseCategory::options()" :value="$expense->category?->value" required />
                <x-input name="amount" type="number" step="0.01" min="0.01" :label="'Amount ('.tenant()->currency.')'" :value="$expense->amount" required />
                <x-input name="spent_on" type="date" label="Date" :value="$expense->spent_on?->toDateString()" required />
                <x-input name="vendor" label="Vendor / payee" :value="$expense->vendor" />
                <x-select name="payment_method" label="Paid with" :options="$methods" :value="$expense->payment_method?->value" placeholder="—" />
                <x-input name="reference" label="Reference" :value="$expense->reference" />
                <div class="sm:col-span-2"><x-textarea name="notes" label="Notes" :value="$expense->notes" rows="2" /></div>
                <div class="space-y-1.5 sm:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Receipt @if ($expense->receipt_path)<span class="text-xs font-normal text-zinc-500">(uploading replaces the current one)</span>@endif</label>
                    <input type="file" name="receipt" accept=".pdf,image/png,image/jpeg,image/webp" class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-semibold dark:text-zinc-400 dark:file:bg-zinc-800 dark:file:text-zinc-200">
                    @error('receipt')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <x-slot:footer>
                @if ($editing)
                    <div class="mr-auto"><x-confirm :action="route('expenses.destroy', $expense)" method="DELETE" title="Delete this expense?" trigger="Delete" confirm="Delete" /></div>
                @endif
                <x-button>{{ $editing ? 'Save' : 'Record expense' }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
