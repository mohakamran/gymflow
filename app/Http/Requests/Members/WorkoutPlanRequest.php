<?php

namespace App\Http\Requests\Members;

use Illuminate\Foundation\Http\FormRequest;

class WorkoutPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('coach', $this->route('member'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'goal' => ['nullable', 'string', 'max:150'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['boolean'],
            'exercises' => ['nullable', 'array', 'max:60'],
            'exercises.*.day' => ['nullable', 'string', 'max:30'],
            'exercises.*.name' => ['required', 'string', 'max:100'],
            'exercises.*.sets' => ['nullable', 'integer', 'min:1', 'max:50'],
            'exercises.*.reps' => ['nullable', 'string', 'max:30'],
            'exercises.*.rest' => ['nullable', 'string', 'max:30'],
            'exercises.*.notes' => ['nullable', 'string', 'max:200'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $exercises = collect($this->input('exercises', []))
            ->filter(fn ($row) => is_array($row) && filled($row['name'] ?? null))
            ->values()
            ->all();

        $this->merge(['exercises' => $exercises, 'is_active' => $this->boolean('is_active')]);
    }
}
