<?php

namespace App\Http\Requests\Members;

use App\Enums\DurationUnit;
use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MembershipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::PlansManage);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_value' => ['required', 'integer', 'min:1', 'max:120'],
            'duration_unit' => ['required', Rule::enum(DurationUnit::class)],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'signup_fee' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_taxable' => ['boolean'],
            'features' => ['nullable', 'string', 'max:2000'],
            'class_limit_per_week' => ['nullable', 'integer', 'min:1', 'max:100'],
            'access_hours' => ['nullable', 'string', 'max:100'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_active' => ['boolean'],
            'is_public' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_taxable' => $this->boolean('is_taxable'),
            'is_active' => $this->boolean('is_active'),
            'is_public' => $this->boolean('is_public'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function planData(): array
    {
        $data = $this->validated();
        $data['features'] = collect(preg_split('/\R/', (string) ($data['features'] ?? '')))->map(fn ($line) => trim($line))->filter()->values()->all();
        $data['signup_fee'] ??= 0;
        $data['discount_percent'] ??= 0;
        $data['sort_order'] ??= 0;

        return $data;
    }
}
