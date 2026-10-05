<?php

namespace App\Http\Controllers\Operations;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\TeamMemberRequest;
use App\Models\ClassSession;
use App\Models\User;
use App\Services\PlanLimits;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(protected TeamService $team) {}

    public function index(Request $request, PlanLimits $limits): View
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->validate([
            'role' => ['nullable', Rule::in([Role::Owner->value, Role::Staff->value, Role::Trainer->value])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->role($filters['role'] ?? [Role::Owner->value, Role::Staff->value, Role::Trainer->value])
            ->with(['staffProfile', 'roles'])
            ->withCount('assignedMembers')
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('team.index', [
            'users' => $users,
            'filters' => $filters,
            'usage' => $limits->usage(tenant())['staff'],
            'upcomingClasses' => ClassSession::query()->upcoming()->reorder()->where('starts_at', '<=', now()->addWeek())
                ->toBase()->selectRaw('trainer_id, count(*) as total')->groupBy('trainer_id')->pluck('total', 'trainer_id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('team.form', ['user' => new User, 'profile' => null, 'role' => Role::Staff->value]);
    }

    public function store(TeamMemberRequest $request): RedirectResponse
    {
        $user = $this->team->invite(tenant(), $request->only(['name', 'email', 'phone', 'role']), $request->profileData());

        return $this->done(redirect()->route('staff.index'), "Invitation sent to {$user->email}.");
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);
        abort_if($user->hasRole(Role::Member->value), 404);

        return view('team.form', ['user' => $user, 'profile' => $user->staffProfile, 'role' => $user->primaryRole()?->value]);
    }

    public function update(TeamMemberRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        $this->team->update($user, $request->only(['name', 'phone', 'role']), $request->profileData(), $request->user());

        return $this->done(redirect()->route('staff.index'), "{$user->name} updated.");
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        $this->team->setActive($user, ! $user->is_active, $request->user());

        return $this->done(back(), $user->is_active ? "{$user->name} reactivated." : "{$user->name} deactivated and signed out.");
    }
}
