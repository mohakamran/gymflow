<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\WhatsAppChannel;
use App\Support\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base class for gym-branded notifications.
 *
 * Notifications are queued, so they run outside the original request; every builder
 * runs inside the gym's tenant context so currency, dates and scoping are correct.
 * Each notification can be switched off per gym via its preference key.
 */
abstract class GymNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Tenant $tenant) {}

    /**
     * Key under settings.notifications that toggles this notification, or null if always on.
     */
    abstract public static function preferenceKey(): ?string;

    abstract protected function buildMail(object $notifiable): MailMessage;

    /**
     * @return array<string, mixed>
     */
    abstract protected function buildArray(object $notifiable): array;

    protected function buildText(object $notifiable): ?string
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $key = static::preferenceKey();

        if ($key !== null && ! $this->tenant->setting("notifications.$key", true)) {
            return [];
        }

        $channels = ['database'];

        if ($notifiable->routeNotificationFor('mail', $this)) {
            $channels[] = 'mail';
        }

        if ($this->buildTextSafely($notifiable) !== null) {
            if ($this->tenant->setting('notifications.sms', false)) {
                $channels[] = SmsChannel::class;
            }

            if ($this->tenant->setting('notifications.whatsapp', false)) {
                $channels[] = WhatsAppChannel::class;
            }
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->inTenant(fn (): MailMessage => $this->buildMail($notifiable)
            ->from(config('mail.from.address'), $this->tenant->name)
            ->salutation('— The team at '.$this->tenant->name));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->inTenant(fn (): array => $this->buildArray($notifiable) + ['tenant_id' => $this->tenant->id]);
    }

    public function toTextMessage(object $notifiable): string
    {
        return $this->tenant->name.': '.$this->buildTextSafely($notifiable);
    }

    protected function buildTextSafely(object $notifiable): ?string
    {
        return $this->inTenant(fn (): ?string => $this->buildText($notifiable));
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    protected function inTenant(callable $callback): mixed
    {
        return app(TenantContext::class)->run($this->tenant, $callback);
    }
}
