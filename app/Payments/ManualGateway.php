<?php

namespace App\Payments;

use App\Models\Payment;

/**
 * Payments taken in person (cash, card terminal, bank transfer). Always succeeds.
 */
class ManualGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return 'Front desk';
    }

    public function charge(PaymentRequest $request): GatewayResult
    {
        return GatewayResult::success($request->reference);
    }

    public function refund(Payment $payment, float $amount): GatewayResult
    {
        return GatewayResult::success();
    }
}
