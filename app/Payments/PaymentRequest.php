<?php

namespace App\Payments;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Member;

final readonly class PaymentRequest
{
    public function __construct(
        public Member $member,
        public float $amount,
        public string $currency,
        public PaymentMethod $method,
        public ?Invoice $invoice = null,
        public ?string $reference = null,
    ) {}
}
