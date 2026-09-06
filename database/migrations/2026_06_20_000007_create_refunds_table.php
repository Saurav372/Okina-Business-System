<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('provider', 40)->index();
            $table->string('refund_type', 40)->index();
            $table->string('status', 40)->index();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->string('reason_code', 80)->nullable();
            $table->text('reason_note')->nullable();
            $table->string('provider_refund_id', 120)->nullable();
            $table->string('provider_payment_id', 120)->nullable()->index();
            $table->string('provider_reference', 160)->nullable();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable()->index();
            $table->timestamp('approved_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_refund_id']);
            $table->index(['order_id', 'created_at']);
            $table->index(['payment_id', 'created_at']);
            $table->index(['status', 'processed_at']);
            $table->index(['refund_type', 'status']);
            $table->index(['requested_by_user_id', 'created_at']);
            $table->index(['approved_by_user_id', 'approved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
