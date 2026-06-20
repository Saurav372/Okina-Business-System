<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 40)->index();
            $table->string('provider_event_id', 160)->nullable();
            $table->string('event_type', 120)->index();
            $table->string('provider_order_id', 120)->nullable()->index();
            $table->string('provider_payment_id', 120)->nullable()->index();
            $table->string('provider_refund_id', 120)->nullable()->index();
            $table->foreignId('payment_attempt_id')->nullable()->constrained('payment_attempts')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('refund_id')->nullable()->constrained('refunds')->nullOnDelete();
            $table->string('processing_status', 40)->index();
            $table->boolean('signature_verified')->default(false);
            $table->json('payload_summary')->nullable();
            $table->string('error_message', 300)->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['provider', 'provider_event_id']);
            $table->index(['provider', 'event_type', 'received_at']);
            $table->index(['processing_status', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');
    }
};