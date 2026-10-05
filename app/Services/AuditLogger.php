<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function __construct(protected TenantContext $tenants, protected Request $request) {}

    /**
     * Write an audit entry. The tenant is taken from the subject when it has one, so
     * platform-level actions on a gym still show up in that gym's history.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        string $event,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?User $user = null,
    ): AuditLog {
        $user ??= $this->request->user();

        return AuditLog::withoutTenancy()->create([
            'tenant_id' => $this->resolveTenantId($subject, $user),
            'user_id' => $user?->getKey(),
            'event' => $event,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'description' => $description,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => $this->request->ip(),
            'user_agent' => mb_substr((string) $this->request->userAgent(), 0, 512),
        ]);
    }

    protected function resolveTenantId(?Model $subject, ?User $user): ?int
    {
        if ($subject instanceof Tenant) {
            return $subject->getKey();
        }

        return $subject?->getAttribute('tenant_id') ?? $user?->tenant_id ?? $this->tenants->id();
    }
}
