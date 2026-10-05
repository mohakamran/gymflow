<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\SubscriptionPlan;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantRegistrationService
{
    public const TRIAL_DAYS = 14;

    public function __construct(protected TenantContext $tenants) {}

    /**
     * Create a gym and its owner account in one transaction.
     *
     * @param  array{gym_name: string, name: string, email: string, password: string, phone?: string|null, country?: string|null, currency?: string|null, timezone?: string|null}  $data
     * @return array{tenant: Tenant, owner: User}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $tenant = new Tenant([
                'name' => $data['gym_name'],
                'slug' => $this->uniqueSlug($data['gym_name']),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'timezone' => $data['timezone'] ?? 'UTC',
                'business_hours' => Tenant::defaultBusinessHours(),
                'settings' => [
                    'tax' => ['enabled' => false, 'rate' => 0, 'label' => 'Tax'],
                    'invoice' => ['prefix' => 'INV-', 'footer' => 'Thank you for training with us!'],
                ],
            ]);
            $tenant->forceFill([
                'status' => TenantStatus::Trial,
                'subscription_plan' => SubscriptionPlan::Starter,
                'trial_ends_at' => now()->addDays(self::TRIAL_DAYS),
            ])->save();

            $owner = $this->tenants->run($tenant, function () use ($data, $tenant): User {
                $owner = new User([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'password' => $data['password'],
                ]);
                $owner->tenant_id = $tenant->id;
                $owner->save();
                $owner->assignRole(Role::Owner->value);

                return $owner;
            });

            return ['tenant' => $tenant, 'owner' => $owner];
        });
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'gym';
        $slug = $base;
        $suffix = 2;

        while (Tenant::withTrashed()->where('slug', $slug)->exists() || in_array($slug, self::reservedSlugs(), true)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @return list<string>
     */
    public static function reservedSlugs(): array
    {
        return ['admin', 'app', 'api', 'login', 'register', 'dashboard', 'settings', 'www', 'portal', 'gyms'];
    }
}
