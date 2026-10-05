<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsForEachTenant;
use App\Enums\Role;
use App\Models\Equipment;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MaintenanceDueNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[Signature('gym:reminders:maintenance {--days=3 : Include equipment due within this many days} {--tenant= : Limit to one gym ID}')]
#[Description('Notify gym owners about equipment due for maintenance')]
class SendMaintenanceReminders extends Command
{
    use RunsForEachTenant;

    public function handle(): int
    {
        $this->forEachTenant(function (Tenant $tenant): void {
            $due = Equipment::query()
                ->maintenanceDue((int) $this->option('days'))
                ->where(fn ($query) => $query->whereNull('maintenance_reminder_sent_at')->orWhere('maintenance_reminder_sent_at', '<', now()->subWeek()))
                ->get();

            if ($due->isEmpty()) {
                return;
            }

            $owners = User::query()->where('is_active', true)->role(Role::Owner->value)->get();
            Notification::send($owners, new MaintenanceDueNotification($tenant, $due->map(fn (Equipment $item): array => [
                'name' => $item->name,
                'due' => format_date($item->next_maintenance_on),
                'overdue' => $item->isMaintenanceOverdue(),
            ])->values()->all()));

            Equipment::query()->whereKey($due->modelKeys())->update(['maintenance_reminder_sent_at' => now()]);
        });

        return self::SUCCESS;
    }
}
