<?php

namespace App\Http\Requests\Settings;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocalizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::SettingsManage);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'currency' => ['required', Rule::in(array_keys(config('gym.currencies')))],
            'timezone' => ['required', 'timezone:all'],
            'locale' => ['required', Rule::in(array_keys(config('gym.locales')))],
            'date_format' => ['required', Rule::in(array_keys(self::dateFormats()))],
            'tax_enabled' => ['boolean'],
            'tax_label' => ['required', 'string', 'max:30'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'invoice_prefix' => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9\-\/]+$/'],
            'invoice_footer' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['tax_enabled' => $this->boolean('tax_enabled')]);
    }

    /**
     * @return array<string, string>
     */
    public static function dateFormats(): array
    {
        return [
            'M j, Y' => now()->format('M j, Y'),
            'd/m/Y' => now()->format('d/m/Y'),
            'm/d/Y' => now()->format('m/d/Y'),
            'Y-m-d' => now()->format('Y-m-d'),
        ];
    }
}
