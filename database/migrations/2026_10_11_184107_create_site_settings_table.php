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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();

            // General / branding
            $table->string('site_name', 255)->default('My Store');
            $table->string('site_tagline', 255)->nullable();
            $table->string('site_logo', 255)->nullable();      // storage path
            $table->string('site_favicon', 255)->nullable();   // storage path
            $table->text('footer_text')->nullable();

            // Theme colors
            $table->string('primary_color', 20)->default('#FF5733');
            $table->string('secondary_color', 20)->default('#C70039');
            $table->string('header_bg_color', 20)->default('#FFFFFF');
            $table->string('footer_bg_color', 20)->default('#1A1A1A');
            $table->string('text_color', 20)->default('#333333');

            // Contact
            $table->string('primary_email', 100)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('business_hours', 255)->nullable();

            // Social
            $table->string('facebook_url', 255)->nullable();
            $table->string('instagram_url', 255)->nullable();
            $table->string('youtube_url', 255)->nullable();
            $table->string('twitter_url', 255)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('tiktok_url', 255)->nullable();

            // Store
            $table->string('currency_symbol', 10)->default('৳');
            $table->string('currency_code', 3)->default('BDT');
            $table->string('currency_position', 6)->default('before'); // before | after
            $table->decimal('delivery_charge_inside_dhaka', 10, 2)->default(60);
            $table->decimal('delivery_charge_outside_dhaka', 10, 2)->default(120);
            $table->decimal('free_shipping_min', 10, 2)->nullable();

            // SEO & tracking
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('meta_keywords', 255)->nullable();
            $table->string('google_analytics_id', 50)->nullable();
            $table->string('facebook_pixel_id', 50)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
