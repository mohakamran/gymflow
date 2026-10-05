<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsForEachTenant;
use App\Models\Membership;
use App\Models\Tenant;
use App\Notifications\MembershipExpiringNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gym:reminders:expiring {--tenant= : Limit to one gym ID}')]
#[Description('Email members whose membership is about to expire')]
class SendMembershipExpiryReminders extends Command
{
    use RunsForEachTenant;

    public function handle(): int
    {
        $this->forEachTenant(function (Tenant $tenant): void {
            $days = (int) $tenant->setting('notifications.expiry_reminder_days', 7);

            Membership::query()
                ->expiringSoon($days)
                ->whereNull('expiry_reminder_sent_at')
                ->with(['member', 'plan'])
                ->each(function (Membership $membership) use ($tenant): void {
                    // Skip if the member already has a follow-on membership lined up.
                    $renewed = Membership::query()->where('member_id', $membership->member_id)->where('starts_on', '>', $membership->ends_on)->exists();

                    if (! $renewed && $membership->member?->email) {
                        $membership->member->notify(new MembershipExpiringNotification($tenant, $membership));
                    }

                    $membership->forceFill(['expiry_reminder_sent_at' => now()])->saveQuietly();
                });
        });

        return self::SUCCESS;
    }
}
