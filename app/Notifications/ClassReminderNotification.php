<?php

namespace App\Notifications;

use App\Models\ClassSession;
use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;

class ClassReminderNotification extends GymNotification
{
    public function __construct(Tenant $tenant, public ClassSession $session)
    {
        parent::__construct($tenant);
    }

    public static function preferenceKey(): ?string
    {
        return 'class_reminders';
    }

    protected function buildMail(object $notifiable): MailMessage
    {
        $name = $this->session->gymClass->name;

        return (new MailMessage)
            ->subject("Reminder: {$name} at ".format_time($this->session->starts_at))
            ->greeting("Hi {$notifiable->first_name},")
            ->line("You're booked into **{$name}** on ".format_date($this->session->starts_at, true).($this->session->location ? " in {$this->session->location}" : '').'.')
            ->line("Can't make it? Cancel from your member portal so someone else can take your spot.");
    }

    protected function buildArray(object $notifiable): array
    {
        return [
            'title' => 'Class reminder',
            'body' => $this->session->gymClass->name.' at '.format_date($this->session->starts_at, true),
            'icon' => 'calendar',
        ];
    }

    protected function buildText(object $notifiable): ?string
    {
        return 'Reminder: '.$this->session->gymClass->name.' at '.format_date($this->session->starts_at, true).'.';
    }
}
