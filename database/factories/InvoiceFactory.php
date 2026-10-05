<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'INV-'.Str::upper(Str::random(6)),
            'issued_on' => now()->toDateString(),
            'due_on' => now()->addWeek()->toDateString(),
            'currency' => 'USD',
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 100,
            'amount_paid' => 0,
            'status' => InvoiceStatus::Unpaid,
        ];
    }
}
