<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('payment_attempt_id')->nullable()->constrained('payment_attempts')->restrictOnDelete();
            $table->unsignedBigInteger('payment_schedule_id')->nullable()->index();
            $table->string('payment_type', 40)->index();
            $table->string('provider', 40)->index();
            $table->string('method', 40)->nullable()->index();
            $table->string('status', 40)->index();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->string('provider_payment_id', 120)->nullable();
            $table->string('provider_order_id', 120)->nullable()->index();
            $table->string('provider_reference', 160)->nullable();
            $table->string('receipt_number', 80)->nullable();
            $table->unsignedBigInteger('gateway_fee_minor')->nullable();
            $table->unsignedBigInteger('net_amount_minor')->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_payment_id']);
            $table->unique('receipt_number');
            $table->index(['order_id', 'paid_at']);
            $table->index(['payment_attempt_id']);
            $table->index(['status', 'paid_at']);
            $table->index(['payment_type', 'status']);
            $table->index(['recorded_by_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};