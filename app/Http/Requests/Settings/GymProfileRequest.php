<?php

namespace App\Http\Requests\Settings;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class GymProfileRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'about' => ['nullable', 'string', 'max:2000'],
            'public_profile_enabled' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country' => $this->filled('country') ? strtoupper((string) $this->input('country')) : null,
            'public_profile_enabled' => $this->boolean('public_profile_enabled'),
        ]);
    }
}
