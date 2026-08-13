<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancellation_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['pending', 'refund_processing', 'refund_failed', 'approved', 'rejected'])->default('pending');
            $table->text('reason');
            $table->dateTime('requested_at');
            $table->string('policy_version');
            $table->json('policy_snapshot');
            $table->dateTime('travel_starts_at');
            $table->bigInteger('seconds_remaining');
            $table->decimal('paid_amount', 12, 2);
            $table->char('currency', 3);
            $table->decimal('suggested_deduction_percentage', 5, 2);
            $table->decimal('suggested_deduction_amount', 12, 2);
            $table->decimal('suggested_refund_amount', 12, 2);
            $table->decimal('final_refund_amount', 12, 2)->nullable();
            $table->decimal('final_deduction_amount', 12, 2)->nullable();
            $table->text('decision_explanation')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('stripe_refund_id')->nullable()->unique();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('failure_code')->nullable();
            $table->string('failure_summary')->nullable();
            $table->enum('failure_disposition', ['definitive', 'indeterminate'])->nullable();
            $table->string('refund_outcome')->nullable();
            $table->timestamp('refund_completed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_requests');
    }
};
