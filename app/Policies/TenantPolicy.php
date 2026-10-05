<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy: a gym user may only touch records of their own gym, and only with the
 * matching permission. Global scopes already hide other gyms' rows; this is a second line
 * of defence for anything resolved without the scope.
 */
abstract class TenantPolicy
{
    protected function sameTenant(User $user, Model $model): bool
    {
        return $user->tenant_id !== null && (int) $user->tenant_id === (int) $model->getAttribute('tenant_id');
    }

    protected function allows(User $user, Model $model, string $permission): bool
    {
        return $this->sameTenant($user, $model) && $user->can($permission);
    }
}
