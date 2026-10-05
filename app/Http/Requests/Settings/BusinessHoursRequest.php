<?php

namespace App\Http\Requests\Settings;

use App\Enums\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BusinessHoursRequest extends FormRequest
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
        $rules = ['hours' => ['required', 'array']];

        foreach (Tenant::DAYS as $day) {
            $rules["hours.$day.closed"] = ['boolean'];
            $rules["hours.$day.open"] = ["exclude_if:hours.$day.closed,true", 'required', 'date_format:H:i'];
            $rules["hours.$day.close"] = ["exclude_if:hours.$day.closed,true", 'required', 'date_format:H:i'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $hours = (array) $this->input('hours', []);

        foreach (Tenant::DAYS as $day) {
            $hours[$day]['closed'] = filter_var($hours[$day]['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $this->merge(['hours' => $hours]);
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (Tenant::DAYS as $day) {
                $data = $this->input("hours.$day");

                if (! ($data['closed'] ?? false) && ($data['open'] ?? '') >= ($data['close'] ?? '') && filled($data['open'] ?? null)) {
                    $validator->errors()->add("hours.$day.close", ucfirst($day).' closing time must be after opening time.');
                }
            }
        }];
    }

    /**
     * @return array<string, array{open: string|null, close: string|null, closed: bool}>
     */
    public function businessHours(): array
    {
        return collect(Tenant::DAYS)->mapWithKeys(function (string $day): array {
            $closed = (bool) $this->input("hours.$day.closed");

            return [$day => [
                'open' => $closed ? null : $this->input("hours.$day.open"),
                'close' => $closed ? null : $this->input("hours.$day.close"),
                'closed' => $closed,
            ]];
        })->all();
    }
}
