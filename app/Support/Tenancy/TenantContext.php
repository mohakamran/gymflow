<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;

/**
 * Holds the tenant (gym) for the current request or job.
 *
 * Registered as a scoped singleton, so it is reset between requests and queued jobs.
 * When no tenant is set (guests, the super admin, console commands) tenant scoping is
 * not applied, so code running outside a request must call set() before touching
 * tenant-owned data.
 */
class TenantContext
{
    protected ?Tenant $tenant = null;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->getKey();
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * Run a callback with the given tenant active, restoring the previous one afterwards.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function run(?Tenant $tenant, callable $callback): mixed
    {
        $previous = $this->tenant;
        $this->tenant = $tenant;

        try {
            return $callback();
        } finally {
            $this->tenant = $previous;
        }
    }
}
