<?php

namespace App\Http\Requests\Settings;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class BrandingRequest extends FormRequest
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
        $mimes = 'mimes:'.implode(',', config('gym.uploads.image_mimes'));

        return [
            'primary_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'image', $mimes, 'max:'.config('gym.uploads.logo_max_kb'), 'dimensions:max_width=4000,max_height=4000'],
            'favicon' => ['nullable', 'image', $mimes, 'max:'.config('gym.uploads.favicon_max_kb'), 'dimensions:max_width=1024,max_height=1024'],
            'remove_logo' => ['boolean'],
            'remove_favicon' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['primary_color.regex' => 'Choose a valid hex color such as #4f46e5.'];
    }
}
