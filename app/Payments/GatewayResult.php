<?php

namespace App\Payments;

final readonly class GatewayResult
{
    public function __construct(
        public bool $successful,
        public bool $pending = false,
        public ?string $gatewayReference = null,
        public ?string $message = null,
    ) {}

    public static function success(?string $gatewayReference = null): self
    {
        return new self(true, false, $gatewayReference);
    }

    public static function pending(string $gatewayReference): self
    {
        return new self(false, true, $gatewayReference);
    }

    public static function failed(string $message): self
    {
        return new self(false, false, null, $message);
    }
}
