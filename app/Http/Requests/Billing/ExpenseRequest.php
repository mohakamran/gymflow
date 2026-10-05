<?php

namespace App\Http\Requests\Billing;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::ExpensesManage);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'spent_on' => ['required', 'date', 'before_or_equal:today'],
            'vendor' => ['nullable', 'string', 'max:150'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:5120'],
        ];
    }
}
