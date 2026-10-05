<?php

namespace App\Payments;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function __construct(protected Container $container) {}

    public function gateway(string $key = 'manual'): PaymentGateway
    {
        $class = config("gym.payment_gateways.$key");

        if (! is_string($class) || ! is_subclass_of($class, PaymentGateway::class)) {
            throw new InvalidArgumentException("Payment gateway [$key] is not configured.");
        }

        return $this->container->make($class);
    }

    /**
     * @return array<string, string> key => label
     */
    public function available(): array
    {
        return collect(config('gym.payment_gateways', []))
            ->mapWithKeys(fn (string $class, string $key): array => [$key => $this->gateway($key)->label()])
            ->all();
    }
}
