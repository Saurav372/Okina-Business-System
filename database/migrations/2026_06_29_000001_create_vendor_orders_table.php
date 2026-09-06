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
        Schema::create('vendor_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->string('public_id', 40)->unique();
            $table->string('status', 40)->default('draft');
            $table->string('payment_status', 40)->default('unpaid');
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('expected_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('subtotal_amount_minor')->default(0);
            $table->unsignedBigInteger('tax_amount_minor')->default(0);
            $table->unsignedBigInteger('shipping_amount_minor')->default(0);
            $table->unsignedBigInteger('discount_amount_minor')->default(0);
            $table->unsignedBigInteger('total_amount_minor')->default(0);
            $table->char('currency', 3)->default('INR');
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index(['vendor_id', 'created_at']);
            $table->index(['status', 'expected_at']);
            $table->index(['payment_status', 'created_at']);
            $table->index('ordered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_orders');
    }
};
