<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\Messaging\LogTextMessenger;
use App\Notifications\Messaging\TextMessenger;
use App\Services\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);

        $this->app->bind(TextMessenger::class, fn () => $this->app->make(
            config('gym.text_messaging.drivers.'.config('gym.text_messaging.driver'), LogTextMessenger::class)
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(10)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8));

        Gate::before(fn (User $user): ?bool => $user->hasRole(Role::SuperAdmin->value) && $user->tenant_id === null ? true : null);

        $this->configureRateLimiting();
        $this->registerAuthAuditing();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('registration', fn (Request $request): Limit => Limit::perHour(5)->by($request->ip()));
        RateLimiter::for('password-reset', fn (Request $request): Limit => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('uploads', fn (Request $request): Limit => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));
    }

    protected function registerAuthAuditing(): void
    {
        Event::listen(function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
                app(AuditLogger::class)->log('auth.login', $event->user, user: $event->user);
            }
        });

        Event::listen(function (Logout $event): void {
            if ($event->user instanceof User) {
                app(AuditLogger::class)->log('auth.logout', $event->user, user: $event->user);
            }
        });

        Event::listen(function (Failed $event): void {
            if ($event->user instanceof User) {
                app(AuditLogger::class)->log('auth.login_failed', $event->user, user: $event->user);
            }
        });

        Event::listen(function (PasswordReset $event): void {
            if ($event->user instanceof User) {
                app(AuditLogger::class)->log('auth.password_reset', $event->user, user: $event->user);
            }
        });
    }
}
