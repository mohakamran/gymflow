<?php

namespace App\Http\Requests\Billing;

use App\Enums\Permission;
use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::InvoicesManage);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', Rule::exists(Member::class, 'id')->where('tenant_id', $this->user()->tenant_id)->whereNull('deleted_at')],
            'issued_on' => ['required', 'date'],
            'due_on' => ['required', 'date', 'after_or_equal:issued_on'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:1000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.taxable' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item) && filled($item['description'] ?? null))
            ->map(fn (array $item) => array_merge($item, ['taxable' => filter_var($item['taxable'] ?? false, FILTER_VALIDATE_BOOLEAN)]))
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }
}
