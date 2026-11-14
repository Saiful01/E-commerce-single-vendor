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
        Schema::create('courier_services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('driver', 30)->default('manual');   // manual | steadfast | pathao | redx | paperfly | ecourier
            $table->string('logo', 255)->nullable();

            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('tracking_url', 255)->nullable();   // https://example.com/track/{tracking_code}
            $table->string('delivery_time', 60)->nullable();   // "2-3 days"
            $table->text('coverage_note')->nullable();

            $table->text('credentials')->nullable();           // encrypted JSON
            $table->boolean('sandbox_mode')->default(false);

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_services');
    }
};
