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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('address_type', ['shipping', 'billing'])->default('shipping');
            $table->string('full_name', 100);
            $table->string('phone', 20);
            $table->string('city', 100);          // district / city
            $table->string('area', 100);          // area / thana
            $table->text('address_line');
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'address_type', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
