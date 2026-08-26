<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 1000, 100000);
        $tax = $subtotal * 0.15;
        $total = $subtotal + $tax;

        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'invoice_number' => 'INV-' . str_pad($this->faker->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'issue_date' => $this->faker->date(),
            'due_date' => $this->faker->date(),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'tax_rate' => 15,
            'discount' => 0,
            'total' => $total,
            'balance_due' => $total,
            'status' => $this->faker->randomElement(['draft', 'sent', 'paid']),
            'currency' => 'LKR',
            'created_by' => null,
        ];
    }
}