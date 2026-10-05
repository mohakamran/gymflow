<?php

namespace App\Notifications;

use App\Models\Announcement;
use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

class AnnouncementNotification extends GymNotification
{
    public function __construct(Tenant $tenant, public Announcement $announcement)
    {
        parent::__construct($tenant);
    }

    public static function preferenceKey(): ?string
    {
        return 'announcements';
    }

    protected function buildMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->announcement->title)->greeting($this->announcement->title);

        foreach (preg_split('/\R{2,}/', trim($this->announcement->body)) ?: [] as $paragraph) {
            $mail->line($paragraph);
        }

        return $mail;
    }

    protected function buildArray(object $notifiable): array
    {
        return [
            'title' => $this->announcement->title,
            'body' => Str::limit($this->announcement->body, 140),
            'icon' => 'bolt',
        ];
    }
}
