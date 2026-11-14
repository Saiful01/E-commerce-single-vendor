<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // About us, terms, privacy, return policy, FAQ, contact ... managed from the admin panel
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('slug', 160)->unique();                 // shop.com/pages/{slug}
            $table->string('template', 20)->default('standard');   // standard | faq | contact
            $table->longText('content')->nullable();               // cleaned HTML
            $table->json('faq_items')->nullable();                 // [{q: "...", a: "..."}] for the faq template

            $table->string('meta_title', 160)->nullable();
            $table->string('meta_description', 300)->nullable();

            $table->boolean('show_in_footer')->default(true);
            $table->boolean('show_in_header')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);         // linked from checkout / register, so it cannot be deleted
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Messages sent from the contact form
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->string('email', 100)->nullable();
            $table->string('subject', 150)->nullable();
            $table->text('message');
            $table->string('ip', 45)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('pages');
    }
};
