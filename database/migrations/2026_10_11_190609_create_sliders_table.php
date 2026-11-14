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
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();

            // Content
            $table->string('title', 255);                       // also used as the internal name
            $table->string('subtitle', 255)->nullable();
            $table->boolean('show_text')->default(true);        // off when the text is already baked into the image

            // Placement
            $table->string('position', 50)->default('home_hero')->index();
            $table->unsignedInteger('serial_order')->default(0);

            // Images (storage paths)
            $table->string('image', 255);
            $table->string('mobile_image', 255)->nullable();

            // Link / button
            $table->string('btn_text', 50)->nullable();
            $table->string('link_type', 20)->default('none');   // none | custom | category | product | brand
            $table->unsignedBigInteger('link_id')->nullable();  // category / product / brand id
            $table->string('btn_link', 255)->nullable();        // used when link_type = custom
            $table->boolean('open_new_tab')->default(false);

            // Look
            $table->string('text_color', 20)->default('#FFFFFF');
            $table->string('text_align', 10)->default('left');  // left | center | right
            $table->unsignedTinyInteger('overlay_opacity')->default(30); // 0 - 100

            // Schedule + status
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->index(['position', 'status', 'serial_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sliders');
    }
};
