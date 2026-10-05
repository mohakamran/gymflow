<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsForEachTenant;
use App\Enums\BookingStatus;
use App\Enums\ClassSessionStatus;
use App\Models\ClassSession;
use App\Models\Tenant;
use App\Notifications\ClassReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gym:reminders:classes {--hours=3 : Remind this many hours ahead} {--tenant= : Limit to one gym ID}')]
#[Description('Remind booked members about upcoming classes')]
class SendClassReminders extends Command
{
    use RunsForEachTenant;

    public function handle(): int
    {
        $this->forEachTenant(function (Tenant $tenant): void {
            ClassSession::query()
                ->where('status', ClassSessionStatus::Scheduled->value)
                ->whereNull('reminder_sent_at')
                ->whereBetween('starts_at', [now(), now()->addHours((int) $this->option('hours'))])
                ->with(['gymClass', 'bookings' => fn ($query) => $query->where('status', BookingStatus::Booked->value)->with('member')])
                ->each(function (ClassSession $session) use ($tenant): void {
                    foreach ($session->bookings as $booking) {
                        if ($booking->member?->email) {
                            $booking->member->notify(new ClassReminderNotification($tenant, $session));
                        }
                    }

                    $session->forceFill(['reminder_sent_at' => now()])->saveQuietly();
                });
        });

        return self::SUCCESS;
    }
}
