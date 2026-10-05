<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Password;

/**
 * Invites a new team member or a member (portal access) to set their password.
 */
class AccountInvitationNotification extends GymNotification
{
    public function __construct(Tenant $tenant, public string $kind = 'team')
    {
        parent::__construct($tenant);
    }

    public static function preferenceKey(): ?string
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    protected function buildMail(object $notifiable): MailMessage
    {
        $token = Password::broker()->createToken($notifiable);
        $url = route('password.reset', ['token' => $token, 'email' => $notifiable->email]);
        $expires = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject("You're invited to {$this->tenant->name}")
            ->greeting("Hi {$notifiable->name},")
            ->line($this->kind === 'member'
                ? "{$this->tenant->name} has set up your member portal. View your membership, invoices, classes and progress online."
                : "You've been added to the {$this->tenant->name} team workspace.")
            ->action('Set your password', $url)
            ->line("This link expires in {$expires} minutes. If it has expired, use \"Forgot password\" on the sign-in page.");
    }

    protected function buildArray(object $notifiable): array
    {
        return ['title' => 'Invitation sent', 'body' => "Invited to {$this->tenant->name}"];
    }
}
