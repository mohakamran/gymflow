<?php

namespace App\Http\Controllers\Settings;

use App\Enums\SubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Models\PlanChangeRequest;
use App\Services\PlanLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The gym's own SaaS subscription: plan, usage against limits, and plan change requests.
 */
class BillingController extends Controller
{
    public function edit(PlanLimits $limits): View
    {
        return view('settings.billing', [
            'tenant' => tenant(),
            'usage' => $limits->usage(tenant()),
            'plans' => SubscriptionPlan::cases(),
            'pending' => PlanChangeRequest::query()->where('status', 'pending')->latest()->first(),
            'history' => PlanChangeRequest::query()->latest()->limit(5)->get(),
        ]);
    }

    public function requestChange(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', Rule::enum(SubscriptionPlan::class), Rule::notIn([tenant()->subscription_plan->value])],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        PlanChangeRequest::query()->where('status', 'pending')->update(['status' => 'withdrawn', 'resolved_at' => now()]);
        PlanChangeRequest::create([
            'requested_by' => $request->user()->id,
            'current_plan' => tenant()->subscription_plan,
            'requested_plan' => $data['plan'],
            'message' => $data['message'] ?? null,
            'status' => 'pending',
        ]);

        return $this->done(back(), 'Plan change requested. Our team will confirm shortly.');
    }
}
