<?php

use App\Enums\VendorPaymentStatus;
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
        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_order_id')->constrained('vendor_orders')->restrictOnDelete();
            $table->string('status', 40)->default(VendorPaymentStatus::PAID->value);
            $table->string('payment_method', 40);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->string('reference', 160)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['vendor_order_id', 'paid_at']);
            $table->index(['status', 'paid_at']);
            $table->index('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_payments');
    }
};
