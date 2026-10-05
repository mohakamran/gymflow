<?php

namespace App\Http\Requests\Members;

use App\Enums\PaymentMethod;
use App\Enums\Permission;
use App\Models\Member;
use App\Models\MembershipPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::MembershipsManage);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'member_id' => [$this->route('membership') ? 'nullable' : 'required', 'integer', Rule::exists(Member::class, 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'membership_plan_id' => ['required', 'integer', Rule::exists(MembershipPlan::class, 'id')->where('tenant_id', $tenantId)->where('is_active', true)->whereNull('deleted_at')],
            'starts_on' => ['required', 'date', 'after_or_equal:'.now()->subYear()->toDateString()],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'auto_renew' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'required_with:payment_amount', Rule::enum(PaymentMethod::class)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['auto_renew' => $this->boolean('auto_renew')]);
    }
}
