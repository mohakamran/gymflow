<?php

namespace App\Console\Commands\Concerns;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;

trait RunsForEachTenant
{
    /**
     * Run the callback once per non-suspended gym with that gym's tenant context active.
     *
     * @param  callable(Tenant): void  $callback
     */
    protected function forEachTenant(callable $callback): void
    {
        $context = app(TenantContext::class);

        Tenant::query()
            ->where('status', '!=', TenantStatus::Suspended->value)
            ->when($this->option('tenant'), fn ($query, $id) => $query->whereKey($id))
            ->each(fn (Tenant $tenant) => $context->run($tenant, fn () => $callback($tenant)));
    }
}
