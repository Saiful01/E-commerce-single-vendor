<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('slug', 60)->unique();            // used in code, never changes
            $table->string('color', 20)->default('#64748B');
            $table->string('description', 255)->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->boolean('is_default')->default(false);      // every new order starts here
            $table->boolean('is_final')->default(false);        // an order cannot move on from here
            $table->boolean('restock_items')->default(false);   // put the items back in stock
            $table->boolean('notify_customer')->default(true);  // send SMS / email on change
            $table->boolean('is_system')->default(false);       // seeded, cannot be deleted
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
