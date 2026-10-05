<?php

namespace App\Http\Requests\Members;

use App\Enums\Gender;
use App\Enums\MemberStatus;
use App\Enums\Permission;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::MembersManage);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        $member = $this->route('member');

        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('members', 'email')->where('tenant_id', $tenantId)->whereNull('deleted_at')->ignore($member?->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'address' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'joined_on' => ['required', 'date'],
            'status' => ['required', Rule::enum(MemberStatus::class)],
            'trainer_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')->where('tenant_id', $tenantId)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'mimes:'.implode(',', config('gym.uploads.image_mimes')), 'max:'.config('gym.uploads.logo_max_kb')],
            'remove_photo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function memberData(): array
    {
        return $this->safe()->except(['photo', 'remove_photo']);
    }
}
