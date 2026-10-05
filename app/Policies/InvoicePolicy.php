<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::InvoicesView);
    }

    public function view(User $user, Invoice $model): bool
    {
        return $this->allows($user, $model, Permission::InvoicesView);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::InvoicesManage);
    }

    public function update(User $user, Invoice $model): bool
    {
        return $this->allows($user, $model, Permission::InvoicesManage);
    }

    public function delete(User $user, Invoice $model): bool
    {
        return $this->allows($user, $model, Permission::InvoicesManage);
    }
}
