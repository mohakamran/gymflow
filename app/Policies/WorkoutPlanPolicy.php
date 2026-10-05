<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkoutPlan;

class WorkoutPlanPolicy extends TenantPolicy
{
    public function update(User $user, WorkoutPlan $plan): bool
    {
        return $this->sameTenant($user, $plan) && (new MemberPolicy)->coach($user, $plan->member);
    }

    public function delete(User $user, WorkoutPlan $plan): bool
    {
        return $this->update($user, $plan);
    }
}
