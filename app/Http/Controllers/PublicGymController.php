<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\ClassSession;
use App\Models\Enquiry;
use App\Models\MembershipPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * SEO-friendly public profile page for each gym that has enabled it.
 */
class PublicGymController extends Controller
{
    public function __construct(protected TenantContext $tenants) {}

    public function show(string $slug): Response
    {
        $tenant = $this->resolve($slug);

        // Render inside the gym's context so scoping, currency and dates use this gym.
        return response($this->tenants->run($tenant, fn () => view('public.gym', [
            'tenant' => $tenant,
            'plans' => MembershipPlan::query()->active()->where('is_public', true)->orderBy('sort_order')->orderBy('price')->get(),
            'trainers' => User::query()->where('is_active', true)->role(Role::Trainer->value)
                ->whereHas('staffProfile', fn ($query) => $query->where('is_public', true))->with('staffProfile')->orderBy('name')->get(),
            'sessionsByDay' => ClassSession::query()->upcoming()->where('starts_at', '<=', now()->addDays(7))
                ->with(['gymClass', 'trainer'])->withCount('activeBookings')->get()
                ->groupBy(fn (ClassSession $session) => $session->starts_at->copy()->setTimezone($tenant->timezone)->toDateString()),
            'hours' => array_replace(Tenant::defaultBusinessHours(), $tenant->business_hours ?? []),
        ])->render()));
    }

    public function enquire(Request $request, string $slug): RedirectResponse
    {
        $tenant = $this->resolve($slug);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:50', 'required_without:email'],
            'interest' => ['nullable', 'string', 'max:150'],
            'message' => ['nullable', 'string', 'max:2000'],
            'website' => ['prohibited'], // honeypot
        ]);

        $this->tenants->run($tenant, function () use ($data, $tenant): void {
            $enquiry = new Enquiry(collect($data)->except('website')->all() + ['status' => 'new']);
            $enquiry->tenant_id = $tenant->id;
            $enquiry->save();
        });

        return redirect()->to(route('public.gym', $tenant->slug).'#contact')->with('enquiry_sent', true);
    }

    public function sitemap(): Response
    {
        $gyms = Tenant::query()->where('public_profile_enabled', true)->where('status', '!=', 'suspended')->get(['slug', 'updated_at']);

        return response()->view('public.sitemap', ['gyms' => $gyms])->header('Content-Type', 'application/xml');
    }

    protected function resolve(string $slug): Tenant
    {
        $tenant = Tenant::query()->where('slug', $slug)->where('public_profile_enabled', true)->first();
        abort_if($tenant === null || ! $tenant->status->canAccessApp(), 404);

        return $tenant;
    }
}
