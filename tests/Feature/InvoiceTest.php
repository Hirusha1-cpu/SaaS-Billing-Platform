<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_invoice()
    {
        // Create user
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@user.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        // Login
        $this->actingAs($user);

        // Create invoice
        $response = $this->post('/api/invoices', [
            'customer_id' => 1,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'items' => [
                [
                    'description' => 'Test Item',
                    'quantity' => 2,
                    'unit_price' => 100,
                ]
            ]
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('invoices', [
            'total' => 200,
        ]);
    }

    public function test_user_can_view_invoices()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/api/invoices');

        $response->assertStatus(200);
    }
}