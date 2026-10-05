<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsForEachTenant;
use App\Models\Tenant;
use App\Services\MembershipService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gym:memberships:refresh {--tenant= : Limit to one gym ID}')]
#[Description('Activate memberships that have started and expire memberships that have ended')]
class RefreshMembershipStatuses extends Command
{
    use RunsForEachTenant;

    public function handle(MembershipService $memberships): int
    {
        $this->forEachTenant(function (Tenant $tenant) use ($memberships): void {
            $result = $memberships->refreshStatuses();

            if ($result['activated'] || $result['expired']) {
                $this->line("{$tenant->name}: {$result['activated']} activated, {$result['expired']} expired");
            }
        });

        return self::SUCCESS;
    }
}
