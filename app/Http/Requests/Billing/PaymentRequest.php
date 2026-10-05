<?php

namespace App\Http\Requests\Billing;

use App\Enums\PaymentMethod;
use App\Enums\Permission;
use App\Models\Invoice;
use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::PaymentsManage);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'member_id' => ['required', 'integer', Rule::exists(Member::class, 'id')->where('tenant_id', $tenantId)],
            'invoice_id' => ['nullable', 'integer', Rule::exists(Invoice::class, 'id')->where('tenant_id', $tenantId)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'paid_at' => ['nullable', 'date', 'before_or_equal:now'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
