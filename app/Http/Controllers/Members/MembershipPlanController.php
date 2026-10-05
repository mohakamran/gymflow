<?php

namespace App\Http\Controllers\Members;

use App\Enums\DurationUnit;
use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Members\MembershipPlanRequest;
use App\Models\MembershipPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MembershipPlanController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', MembershipPlan::class);

        return view('plans.index', [
            'plans' => MembershipPlan::query()
                ->withCount(['memberships as active_count' => fn ($query) => $query->where('status', MembershipStatus::Active->value)])
                ->orderBy('sort_order')
                ->orderBy('price')
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', MembershipPlan::class);

        return view('plans.form', ['plan' => new MembershipPlan([
            'duration_value' => 1, 'duration_unit' => DurationUnit::Month, 'is_active' => true, 'is_public' => true,
            'is_taxable' => true, 'color' => tenant()->primary_color, 'price' => null,
        ])]);
    }

    public function store(MembershipPlanRequest $request): RedirectResponse
    {
        $plan = MembershipPlan::create($request->planData());

        return $this->done(redirect()->route('plans.index'), "{$plan->name} plan created.");
    }

    public function edit(MembershipPlan $plan): View
    {
        $this->authorize('update', $plan);

        return view('plans.form', ['plan' => $plan]);
    }

    public function update(MembershipPlanRequest $request, MembershipPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);
        $plan->update($request->planData());

        return $this->done(redirect()->route('plans.index'), 'Plan updated. Existing memberships keep their original price.');
    }

    public function destroy(MembershipPlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);

        if ($plan->memberships()->whereIn('status', [MembershipStatus::Active->value, MembershipStatus::Pending->value])->exists()) {
            $plan->update(['is_active' => false]);

            return $this->done(back(), 'This plan has active members, so it was retired instead of deleted.', 'info');
        }

        $plan->delete();

        return $this->done(redirect()->route('plans.index'), 'Plan deleted.');
    }
}
