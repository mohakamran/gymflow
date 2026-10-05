<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Expense;
use App\Models\User;

class ExpensePolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ExpensesManage);
    }

    public function view(User $user, Expense $model): bool
    {
        return $this->allows($user, $model, Permission::ExpensesManage);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ExpensesManage);
    }

    public function update(User $user, Expense $model): bool
    {
        return $this->allows($user, $model, Permission::ExpensesManage);
    }

    public function delete(User $user, Expense $model): bool
    {
        return $this->allows($user, $model, Permission::ExpensesManage);
    }
}
