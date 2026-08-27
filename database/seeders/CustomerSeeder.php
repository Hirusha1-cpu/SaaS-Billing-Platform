<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        if ($company) {
            $customers = [
                [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'phone' => '+94123456781',
                    'address' => '45 Park Street, Colombo 02',
                    'city' => 'Colombo',
                    'country' => 'Sri Lanka',
                    'company_name' => 'ABC Pvt Ltd',
                    'is_active' => true,
                ],
                [
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                    'phone' => '+94123456782',
                    'address' => '78 Galle Road, Colombo 03',
                    'city' => 'Colombo',
                    'country' => 'Sri Lanka',
                    'company_name' => 'XYZ Enterprises',
                    'is_active' => true,
                ],
                [
                    'name' => 'Mike Johnson',
                    'email' => 'mike@example.com',
                    'phone' => '+94123456783',
                    'address' => '12 Kandy Road, Kandy',
                    'city' => 'Kandy',
                    'country' => 'Sri Lanka',
                    'company_name' => 'Tech Solutions',
                    'is_active' => true,
                ],
                [
                    'name' => 'Sarah Williams',
                    'email' => 'sarah@example.com',
                    'phone' => '+94123456784',
                    'address' => '56 Negombo Road, Negombo',
                    'city' => 'Negombo',
                    'country' => 'Sri Lanka',
                    'company_name' => 'Global Traders',
                    'is_active' => true,
                ],
                [
                    'name' => 'David Brown',
                    'email' => 'david@example.com',
                    'phone' => '+94123456785',
                    'address' => '90 Matara Road, Matara',
                    'city' => 'Matara',
                    'country' => 'Sri Lanka',
                    'company_name' => 'Brown Industries',
                    'is_active' => true,
                ],
            ];

            foreach ($customers as $customer) {
                Customer::create(array_merge($customer, [
                    'company_id' => $company->id,
                ]));
            }
        }

        // Create additional customers using factory (if needed)
        // Customer::factory(10)->create();
    }
}