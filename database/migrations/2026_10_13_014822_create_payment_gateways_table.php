<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();               // what customers see at checkout
            $table->string('driver', 30);                        // cod | bank_transfer | bkash_manual | ... | sslcommerz | stripe
            $table->string('logo', 255)->nullable();
            $table->string('instructions', 500)->nullable();     // shown to the customer at checkout

            $table->text('credentials')->nullable();             // encrypted JSON (keys, account numbers ...)
            $table->boolean('sandbox_mode')->default(false);

            $table->string('fee_type', 12)->default('none');     // none | percentage | fixed
            $table->decimal('fee_value', 10, 2)->default(0);
            $table->decimal('min_amount', 10, 2)->nullable();    // order total range this method accepts
            $table->decimal('max_amount', 10, 2)->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
