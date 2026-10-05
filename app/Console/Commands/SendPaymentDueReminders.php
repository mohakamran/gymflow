<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsForEachTenant;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Notifications\PaymentDueNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gym:reminders:payment-due {--tenant= : Limit to one gym ID}')]
#[Description('Remind members about overdue invoices (at most once a week per invoice)')]
class SendPaymentDueReminders extends Command
{
    use RunsForEachTenant;

    public function handle(): int
    {
        $this->forEachTenant(function (Tenant $tenant): void {
            Invoice::query()
                ->overdue()
                ->where(fn ($query) => $query->whereNull('due_reminder_sent_at')->orWhere('due_reminder_sent_at', '<', now()->subWeek()))
                ->with('member')
                ->each(function (Invoice $invoice) use ($tenant): void {
                    if ($invoice->member?->email) {
                        $invoice->member->notify(new PaymentDueNotification($tenant, $invoice));
                    }

                    $invoice->forceFill(['due_reminder_sent_at' => now()])->saveQuietly();
                });
        });

        return self::SUCCESS;
    }
}
