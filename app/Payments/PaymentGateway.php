<?php

namespace App\Payments;

use App\Models\Payment;

/**
 * Contract for payment providers. The built-in "manual" gateway records payments taken at the
 * front desk; online providers (Stripe, PayPal, local gateways) implement the same contract and
 * are registered in config/gym.php under "payment_gateways".
 */
interface PaymentGateway
{
    public function key(): string;

    public function label(): string;

    /**
     * Capture a payment. Manual payments succeed immediately; online gateways may return a
     * pending result that a webhook later completes.
     */
    public function charge(PaymentRequest $request): GatewayResult;

    public function refund(Payment $payment, float $amount): GatewayResult;
}
