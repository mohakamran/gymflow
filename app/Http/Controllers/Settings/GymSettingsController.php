<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BrandingRequest;
use App\Http\Requests\Settings\BusinessHoursRequest;
use App\Http\Requests\Settings\GymProfileRequest;
use App\Http\Requests\Settings\LocalizationRequest;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GymSettingsController extends Controller
{
    protected Tenant $tenant;

    public function __construct(TenantContext $tenants)
    {
        $this->tenant = $tenants->get();
    }

    public function editProfile(): View
    {
        return view('settings.profile', ['tenant' => $this->tenant]);
    }

    public function updateProfile(GymProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $about = $data['about'] ?? null;
        unset($data['about']);

        $this->tenant->fill($data);
        $this->tenant->settings = array_replace_recursive($this->tenant->settings ?? [], ['profile' => ['about' => $about]]);
        $this->tenant->save();

        return $this->saved('settings.profile.edit', 'Gym profile saved.');
    }

    public function editBranding(): View
    {
        return view('settings.branding', ['tenant' => $this->tenant]);
    }

    public function updateBranding(BrandingRequest $request): RedirectResponse
    {
        $this->tenant->primary_color = strtolower($request->string('primary_color'));
        $directory = 'tenants/'.$this->tenant->id.'/branding';

        foreach (['logo', 'favicon'] as $field) {
            $column = $field.'_path';

            if ($request->boolean('remove_'.$field) || $request->hasFile($field)) {
                if ($this->tenant->$column) {
                    Storage::disk('public')->delete($this->tenant->$column);
                }

                $this->tenant->$column = $request->hasFile($field)
                    ? $request->file($field)->store($directory, 'public')
                    : null;
            }
        }

        $this->tenant->save();

        return $this->saved('settings.branding.edit', 'Branding updated.');
    }

    public function editHours(): View
    {
        return view('settings.hours', [
            'tenant' => $this->tenant,
            'hours' => array_replace(Tenant::defaultBusinessHours(), $this->tenant->business_hours ?? []),
        ]);
    }

    public function updateHours(BusinessHoursRequest $request): RedirectResponse
    {
        $this->tenant->business_hours = $request->businessHours();
        $this->tenant->settings = array_replace_recursive($this->tenant->settings ?? [], ['onboarding' => ['hours_confirmed' => true]]);
        $this->tenant->save();

        return $this->saved('settings.hours.edit', 'Business hours saved.');
    }

    public function editLocalization(): View
    {
        return view('settings.localization', [
            'tenant' => $this->tenant,
            'dateFormats' => LocalizationRequest::dateFormats(),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    public function updateLocalization(LocalizationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->tenant->fill([
            'currency' => $data['currency'],
            'timezone' => $data['timezone'],
            'locale' => $data['locale'],
        ]);
        $this->tenant->settings = array_replace_recursive($this->tenant->settings ?? [], [
            'date_format' => $data['date_format'],
            'tax' => ['enabled' => $data['tax_enabled'], 'label' => $data['tax_label'], 'rate' => (float) $data['tax_rate']],
            'invoice' => ['prefix' => $data['invoice_prefix'], 'footer' => $data['invoice_footer'] ?? ''],
        ]);
        $this->tenant->save();

        return $this->saved('settings.localization.edit', 'Regional, tax & invoice settings saved.');
    }

    protected function saved(string $route, string $message): RedirectResponse
    {
        return redirect()->route($route)->with('toast', ['type' => 'success', 'message' => $message]);
    }
}
