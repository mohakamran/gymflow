<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Isolates a model's rows per gym: queries are filtered to the active tenant and new
 * rows are stamped with it. A record can never be moved to another tenant.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $tenantId = app(TenantContext::class)->id();

            if ($model->getAttribute('tenant_id') === null && $tenantId !== null) {
                $model->setAttribute('tenant_id', $tenantId);
            }
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('tenant_id') && $model->getOriginal('tenant_id') !== null) {
                throw new \LogicException('A record cannot be moved to another tenant.');
            }
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Query across all tenants. Only for platform-level (super admin) code.
     *
     * @return Builder<static>
     */
    public static function withoutTenancy(): Builder
    {
        return static::withoutGlobalScope(TenantScope::class);
    }
}
