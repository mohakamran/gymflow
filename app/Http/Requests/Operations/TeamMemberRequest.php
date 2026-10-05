<?php

namespace App\Http\Requests\Operations;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::StaffManage);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $creating = $this->route('user') === null;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => $creating ? ['required', 'email', 'max:255', Rule::unique('users', 'email')] : ['prohibited'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', Rule::in([Role::Owner->value, Role::Staff->value, Role::Trainer->value])],
            'job_title' => ['nullable', 'string', 'max:100'],
            'specialization' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'hired_on' => ['nullable', 'date'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'is_public' => ['boolean'],
            'working_hours' => ['nullable', 'array'],
            'working_hours.*.start' => ['nullable', 'date_format:H:i'],
            'working_hours.*.end' => ['nullable', 'date_format:H:i'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_public' => $this->boolean('is_public')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function profileData(): array
    {
        $hours = collect(Tenant::DAYS)->mapWithKeys(fn (string $day): array => [$day => [
            'start' => $this->input("working_hours.$day.start") ?: null,
            'end' => $this->input("working_hours.$day.end") ?: null,
        ]])->filter(fn (array $slot): bool => $slot['start'] && $slot['end'])->all();

        return [
            'job_title' => $this->input('job_title'),
            'specialization' => $this->input('specialization'),
            'bio' => $this->input('bio'),
            'hired_on' => $this->input('hired_on'),
            'hourly_rate' => $this->input('hourly_rate'),
            'is_public' => $this->boolean('is_public'),
            'working_hours' => $hours,
        ];
    }
}
