<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 40)->unique();
            $table->string('order_type', 32)->index();
            $table->string('order_source', 40)->index();
            $table->string('status', 32)->index();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->foreignId('billing_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->json('customer_snapshot');
            $table->json('shipping_address_snapshot')->nullable();
            $table->json('billing_address_snapshot')->nullable();
            $table->unsignedBigInteger('subtotal_amount_minor');
            $table->unsignedBigInteger('discount_amount_minor')->default(0);
            $table->unsignedBigInteger('shipping_amount_minor')->default(0);
            $table->unsignedBigInteger('tax_amount_minor')->default(0);
            $table->unsignedBigInteger('total_amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->boolean('design_approved')->default(false);
            $table->timestamp('design_approved_at')->nullable();
            $table->foreignId('design_approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('design_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('placed_at')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable()->index();
            $table->timestamp('refunded_at')->nullable()->index();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['order_type', 'status', 'created_at']);
            $table->index(['order_source', 'created_at']);
            $table->index(['shipping_address_id']);
            $table->index(['billing_address_id']);
            $table->index(['deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
