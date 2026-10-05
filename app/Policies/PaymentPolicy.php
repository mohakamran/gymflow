<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::PaymentsView);
    }

    public function view(User $user, Payment $model): bool
    {
        return $this->allows($user, $model, Permission::PaymentsView);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::PaymentsManage);
    }

    public function update(User $user, Payment $model): bool
    {
        return $this->allows($user, $model, Permission::PaymentsManage);
    }

    public function delete(User $user, Payment $model): bool
    {
        return $this->allows($user, $model, Permission::PaymentsManage);
    }
}
