<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Hands out gap-free, per-gym sequential numbers (invoice numbers, member codes).
 * The tenant row is locked so concurrent requests never receive the same number.
 */
class SequenceGenerator
{
    public function nextInvoiceNumber(Tenant $tenant): string
    {
        $next = $this->increment($tenant, 'invoice_counter');
        $prefix = (string) $tenant->setting('invoice.prefix', 'INV-');

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function nextMemberCode(Tenant $tenant): string
    {
        return 'M'.str_pad((string) $this->increment($tenant, 'member_counter'), 5, '0', STR_PAD_LEFT);
    }

    protected function increment(Tenant $tenant, string $column): int
    {
        return DB::transaction(function () use ($tenant, $column): int {
            $current = (int) Tenant::query()->whereKey($tenant->getKey())->lockForUpdate()->value($column);
            $next = $current + 1;

            Tenant::query()->whereKey($tenant->getKey())->update([$column => $next]);
            $tenant->setAttribute($column, $next)->syncOriginalAttribute($column);

            return $next;
        });
    }
}
