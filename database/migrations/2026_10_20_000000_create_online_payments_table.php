<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row for every time a customer is sent to an online gateway (SSLCommerz).
        // The money itself is recorded in order_payments only after the gateway confirms it.
        Schema::create('online_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_gateway_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tran_id', 40)->unique();               // sent to the gateway, ORD-261020-1234-AB12CD
            $table->string('provider_ref', 255)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 5)->default('BDT');
            $table->string('status', 12)->default('initiated');    // initiated | paid | failed | cancelled | review
            $table->string('val_id', 100)->nullable();
            $table->string('bank_tran_id', 100)->nullable();
            $table->string('card_type', 60)->nullable();
            $table->json('response')->nullable();                  // what the gateway sent back
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_payments');
    }
};
