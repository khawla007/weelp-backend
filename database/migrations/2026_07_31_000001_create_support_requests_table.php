<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('client_request_id')->unique();
            $table->string('reference', 32)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->string('email');
            $table->string('topic', 64);
            $table->text('message');
            $table->string('item_type', 32);
            $table->unsignedBigInteger('item_id');
            $table->string('item_title');
            $table->string('city_slug');
            $table->string('item_slug');
            $table->string('page_url', 2048);
            $table->string('status', 32)->default('open');
            $table->timestamp('traveler_notified_at')->nullable();
            $table->timestamp('traveler_notification_failed_at')->nullable();
            $table->timestamp('support_notified_at')->nullable();
            $table->timestamp('support_notification_failed_at')->nullable();
            $table->timestamps();

            $table->index(['item_type', 'item_id']);
            $table->index(['status', 'created_at']);
            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};
