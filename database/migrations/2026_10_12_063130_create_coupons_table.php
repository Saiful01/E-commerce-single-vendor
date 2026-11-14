<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();
            $table->string('name', 100)->nullable();          // internal label
            $table->string('description', 500)->nullable();   // can be shown to customers

            // Discount
            $table->string('type', 20)->default('percentage'); // percentage | fixed | free_shipping
            $table->decimal('value', 10, 2)->default(0);
            $table->decimal('max_discount_amount', 10, 2)->nullable(); // cap for percentage coupons
            $table->decimal('min_order_amount', 10, 2)->nullable();

            // Limits
            $table->unsignedInteger('usage_limit')->nullable();          // total uses, null = unlimited
            $table->unsignedInteger('usage_limit_per_user')->nullable(); // null = unlimited
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('first_order_only')->default(false);

            // Scope
            $table->string('applies_to', 20)->default('all');  // all | categories | brands | products
            $table->boolean('exclude_discounted')->default(false); // skip items that already have a discount price

            // Validity
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'expires_at']);
        });

        // Which categories / brands / products a coupon is limited to
        Schema::create('category_coupon', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'coupon_id']);
        });

        Schema::create('brand_coupon', function (Blueprint $table) {
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->primary(['brand_id', 'coupon_id']);
        });

        Schema::create('coupon_product', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'product_id']);
        });

        // One row every time a coupon is used
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('order_id')->nullable()->index(); // no foreign key until the orders table exists
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupon_product');
        Schema::dropIfExists('brand_coupon');
        Schema::dropIfExists('category_coupon');
        Schema::dropIfExists('coupons');
    }
};
