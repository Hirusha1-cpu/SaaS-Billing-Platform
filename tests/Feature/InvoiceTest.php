<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_user_can_create_invoice()
    {
        // Create company
        $company = Company::factory()->create();

        // Create user
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        // Create customer
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
        ]);

        // Login
        $this->actingAs($user);

        // Create invoice
        $response = $this->post('/api/invoices', [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'tax_rate' => 15,
            'currency' => 'LKR',
            'items' => [
                [
                    'description' => 'Test Item 1',
                    'quantity' => 2,
                    'unit_price' => 1000,
                ],
                [
                    'description' => 'Test Item 2',
                    'quantity' => 1,
                    'unit_price' => 500,
                ]
            ]
        ]);

        // Check response
        $response->assertStatus(201);
        
        // Check database
        $this->assertDatabaseHas('invoices', [
            'customer_id' => $customer->id,
            'status' => 'draft',
        ]);
    }

    public function test_user_can_view_invoices()
    {
        // Create company
        $company = Company::factory()->create();

        // Create user
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        // Create some invoices
        Invoice::factory()
            ->count(3)
            ->create([
                'company_id' => $company->id,
            ]);

        // Login
        $this->actingAs($user);

        // Get invoices
        $response = $this->get('/api/invoices');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'invoice_number',
                    'total',
                    'status',
                ]
            ]
        ]);
    }

    public function test_user_can_view_single_invoice()
    {
        // Create company
        $company = Company::factory()->create();

        // Create user
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        // Create customer
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
        ]);

        // Create invoice
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        // Login
        $this->actingAs($user);

        // Get invoice
        $response = $this->get("/api/invoices/{$invoice->id}");

        $response->assertStatus(200);
        
        // Check response structure - data wrapper එක තියෙනවා
        $response->assertJsonStructure([
            'data' => [
                'id',
                'invoice_number',
                'total',
                'status',
            ]
        ]);

        // Check specific values - data wrapper එක ඇතුලේ check කරන්න
        $response->assertJson([
            'data' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]
        ]);
    }
}