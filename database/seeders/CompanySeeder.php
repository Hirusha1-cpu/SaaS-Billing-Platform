<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::create([
            'name' => 'Demo Company',
            'email' => 'demo@company.com',
            'phone' => '+94123456789',
            'address' => '123 Main Street, Colombo',
            'city' => 'Colombo',
            'country' => 'Sri Lanka',
            'tax_rate' => 15.00,
            'currency' => 'LKR',
            'is_active' => true,
        ]);

        // Create additional companies using factory (if needed)
        // Company::factory(5)->create();
    }
}