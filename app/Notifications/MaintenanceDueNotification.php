<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;

class MaintenanceDueNotification extends GymNotification
{
    /**
     * @param  list<array{name: string, due: string, overdue: bool}>  $items
     */
    public function __construct(Tenant $tenant, public array $items)
    {
        parent::__construct($tenant);
    }

    public static function preferenceKey(): ?string
    {
        return 'maintenance';
    }

    protected function buildMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(count($this->items).' equipment item(s) due for maintenance')
            ->greeting("Hi {$notifiable->name},")
            ->line('The following equipment is due for maintenance:');

        foreach ($this->items as $item) {
            $mail->line("• **{$item['name']}** — ".($item['overdue'] ? 'overdue since ' : 'due ').$item['due']);
        }

        return $mail->action('Open equipment', route('equipment.index', ['maintenance' => 'due']));
    }

    protected function buildArray(object $notifiable): array
    {
        return [
            'title' => 'Maintenance due',
            'body' => count($this->items).' equipment item(s) need maintenance: '.collect($this->items)->pluck('name')->take(3)->join(', '),
            'icon' => 'wrench',
            'url' => route('equipment.index', ['maintenance' => 'due']),
        ];
    }
}
