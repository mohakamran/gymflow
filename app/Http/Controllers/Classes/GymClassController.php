<?php

namespace App\Http\Controllers\Classes;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\GymClass;
use App\Models\User;
use App\Services\ClassScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GymClassController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', GymClass::class);

        return view('classes.types', [
            'classes' => GymClass::query()->with('trainer')->withCount(['sessions as upcoming_count' => fn ($query) => $query->upcoming()])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', GymClass::class);

        return view('classes.type-form', ['class' => new GymClass(['capacity' => 20, 'duration_minutes' => 60, 'color' => tenant()->primary_color, 'is_active' => true, 'allow_member_booking' => true]), 'trainers' => $this->trainers()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', GymClass::class);
        $class = GymClass::create($this->validated($request));

        return $this->done(redirect()->route('classes.sessions.create', ['class' => $class->id]), "{$class->name} created. Now add it to the schedule.");
    }

    public function edit(GymClass $class): View
    {
        $this->authorize('update', $class);

        return view('classes.type-form', ['class' => $class, 'trainers' => $this->trainers()]);
    }

    public function update(Request $request, GymClass $class): RedirectResponse
    {
        $this->authorize('update', $class);
        $class->update($this->validated($request));

        return $this->done(redirect()->route('classes.types.index'), 'Class updated. Already scheduled sessions keep their settings.');
    }

    public function destroy(GymClass $class): RedirectResponse
    {
        $this->authorize('delete', $class);
        $class->sessions()->upcoming()->get()->each(fn ($session) => app(ClassScheduleService::class)->cancelSession($session));
        $class->delete();

        return $this->done(redirect()->route('classes.types.index'), "{$class->name} removed and its upcoming sessions cancelled.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $request->merge(['is_active' => $request->boolean('is_active'), 'allow_member_booking' => $request->boolean('allow_member_booking')]);

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'trainer_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')->where('tenant_id', $request->user()->tenant_id)],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'location' => ['nullable', 'string', 'max:100'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'allow_member_booking' => ['boolean'],
            'is_active' => ['boolean'],
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    protected function trainers(): Collection
    {
        return User::query()->where('is_active', true)->role([Role::Trainer->value, Role::Owner->value])->orderBy('name')->pluck('name', 'id');
    }
}
