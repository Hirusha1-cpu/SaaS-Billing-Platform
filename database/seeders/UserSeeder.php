<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        if ($company) {
            // Admin User
            User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
                'role' => 'admin',
                'is_active' => true,
            ]);

            // Accountant User
            User::create([
                'name' => 'Accountant User',
                'email' => 'accountant@example.com',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
                'role' => 'accountant',
                'is_active' => true,
            ]);

            // Viewer User
            User::create([
                'name' => 'Viewer User',
                'email' => 'viewer@example.com',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
                'role' => 'viewer',
                'is_active' => true,
            ]);
        }

        // Create additional users using factory (if needed)
        // User::factory(5)->create();
    }
}