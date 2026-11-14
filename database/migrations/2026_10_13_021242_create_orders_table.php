<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Customer details as they were when the order was placed
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20)->index();
            $table->string('customer_email', 100)->nullable();

            // Delivery address (copied, so later address edits never change old orders)
            $table->string('shipping_name', 100);
            $table->string('shipping_phone', 20);
            $table->string('shipping_city', 100);
            $table->string('shipping_area', 100);
            $table->text('shipping_address');
            $table->string('delivery_zone', 20)->default('inside_dhaka'); // inside_dhaka | outside_dhaka

            $table->foreignId('order_status_id')->constrained('order_statuses');

            // Payment (paid_amount and payment_status are worked out from order_payments)
            $table->foreignId('payment_gateway_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_status', 20)->default('unpaid');       // unpaid | partial | paid | refunded
            $table->decimal('paid_amount', 12, 2)->default(0);             // money received minus money refunded
            $table->string('transaction_id', 100)->nullable();             // latest transaction id

            // Shipping
            $table->foreignId('courier_service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tracking_code', 100)->nullable();
            // Steadfast (filled when the parcel is booked from the order page)
            $table->string('courier_consignment_id', 40)->nullable()->index();
            $table->string('courier_status', 50)->nullable();              // in_review | pending | hold | delivered | cancelled ...
            $table->timestamp('courier_booked_at')->nullable();

            // Coupon
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code', 50)->nullable();

            // Money
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('shipping_charge', 12, 2)->default(0);
            $table->decimal('payment_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('returned_total', 12, 2)->default(0);          // value of goods already returned

            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('placed_by', 20)->default('customer');          // customer | admin

            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('restocked_at')->nullable();                 // set once the items went back to stock
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            // Snapshot, so the order still reads correctly if the product changes or is deleted
            $table->string('name', 255);
            $table->string('sku', 100)->nullable();
            $table->string('variant_label', 100)->nullable();   // "Size: XL"
            $table->string('image', 255)->nullable();

            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('returned_quantity')->default(0);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_status_id')->constrained('order_statuses');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // who changed it
            $table->string('note', 500)->nullable();
            $table->boolean('notified')->default(false);        // the customer was told
            $table->timestamp('created_at')->useCurrent();
        });

        // Every payment and refund on an order. An order can be paid in many parts.
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_gateway_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 10)->default('payment');        // payment | refund
            $table->string('status', 10)->default('confirmed');    // pending (customer says they paid) | confirmed | rejected
            $table->decimal('amount', 12, 2);                      // always positive, the type decides the direction
            $table->string('transaction_id', 100)->nullable();
            $table->string('note', 500)->nullable();
            $table->string('source', 10)->default('admin');        // admin | customer
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();      // who entered it
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        // Returns work per item: requested -> approved -> completed (or rejected)
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 30)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 10)->default('customer');     // customer | admin
            $table->string('status', 12)->default('requested');    // requested | approved | rejected | completed
            $table->string('reason', 40);
            $table->text('details')->nullable();
            $table->decimal('items_value', 12, 2)->default(0);     // value of the returned goods (coupon share already taken off)
            $table->decimal('refund_amount', 12, 2)->default(0);   // money actually given back
            $table->boolean('restocked')->default(false);
            $table->text('admin_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('order_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('value', 12, 2);
            $table->timestamps();
        });

        // What the courier told us about a parcel (status changes, tracking steps). Also used to ignore repeated webhooks.
        Schema::create('courier_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('notification_type', 40);               // booked | status | tracking
            $table->string('status', 50)->nullable();
            $table->string('message', 500)->nullable();
            $table->json('payload')->nullable();
            $table->string('event_key', 100)->nullable()->unique(); // webhook idempotency key
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_events');
        Schema::dropIfExists('order_return_items');
        Schema::dropIfExists('order_returns');
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
