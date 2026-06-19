<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 40)->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('provider', 40)->index();
            $table->string('attempt_type', 40)->index();
            $table->string('status', 32)->index();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->string('idempotency_key', 120)->unique();
            $table->string('gateway_order_id', 120)->nullable()->index();
            $table->string('gateway_payment_id', 120)->nullable()->index();
            $table->string('gateway_reference', 120)->nullable()->index();
            $table->text('checkout_url')->nullable();
            $table->timestamp('initiated_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index(['provider', 'status', 'created_at']);
            $table->index(['attempt_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
