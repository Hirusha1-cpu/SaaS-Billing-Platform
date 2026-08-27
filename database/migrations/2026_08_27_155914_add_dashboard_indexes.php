<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if indexes exist before creating
        $this->createIndexIfNotExists('invoices', ['company_id', 'status'], 'invoices_company_id_status_index');
        $this->createIndexIfNotExists('invoices', ['company_id', 'created_at'], 'invoices_company_id_created_at_index');
        $this->createIndexIfNotExists('invoices', ['company_id', 'paid_at'], 'invoices_company_id_paid_at_index');
        $this->createIndexIfNotExists('payments', ['company_id', 'payment_date'], 'payments_company_id_payment_date_index');
        $this->createIndexIfNotExists('payments', ['company_id', 'status'], 'payments_company_id_status_index');
        $this->createIndexIfNotExists('customers', ['company_id', 'is_active'], 'customers_company_id_is_active_index');
        $this->createIndexIfNotExists('subscriptions', ['company_id', 'status'], 'subscriptions_company_id_status_index');
    }

    private function createIndexIfNotExists($table, $columns, $indexName)
    {
        // Check if index exists
        $indexExists = DB::select("
            SELECT 1 
            FROM pg_indexes 
            WHERE schemaname = 'public' 
            AND tablename = ? 
            AND indexname = ?
        ", [$table, $indexName]);

        if (empty($indexExists)) {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    public function down(): void
    {
        // Drop indexes if they exist
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndexIfExists('invoices_company_id_status_index');
            $table->dropIndexIfExists('invoices_company_id_created_at_index');
            $table->dropIndexIfExists('invoices_company_id_paid_at_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndexIfExists('payments_company_id_payment_date_index');
            $table->dropIndexIfExists('payments_company_id_status_index');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndexIfExists('customers_company_id_is_active_index');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndexIfExists('subscriptions_company_id_status_index');
        });
    }
};