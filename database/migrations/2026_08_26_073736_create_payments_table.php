<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('LKR');
            $table->enum('payment_method', ['stripe', 'paypal', 'bank_transfer', 'cash', 'check'])->default('stripe');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'partially_refunded'])->default('pending');
            $table->string('transaction_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->string('receipt_url')->nullable();
            $table->timestamp('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id']);
            $table->index(['invoice_id']);
            $table->index(['customer_id']);
            $table->index(['status']);
            $table->index(['payment_date']);
            $table->index(['transaction_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};