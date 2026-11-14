<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('custom_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // recipient (customer)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('channel', ['email', 'sms'])->default('email');
            $table->string('recipient'); // email or phone
            $table->string('subject')->nullable();
            $table->text('body');
            $table->enum('status', ['draft', 'sent', 'failed'])->default('draft')->index();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_messages');
    }
};
