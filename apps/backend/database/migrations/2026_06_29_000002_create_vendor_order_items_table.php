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
        Schema::create('vendor_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_order_id')->constrained('vendor_orders')->cascadeOnDelete();
            $table->foreignId('product_sku_id')->constrained('product_skus')->restrictOnDelete();
            $table->string('sku_code_snapshot', 80);
            $table->string('product_name_snapshot', 180)->nullable();
            $table->unsignedInteger('quantity_ordered');
            $table->unsignedInteger('quantity_received')->default(0);
            $table->unsignedBigInteger('unit_cost_minor');
            $table->unsignedBigInteger('tax_amount_minor')->default(0);
            $table->unsignedBigInteger('line_total_minor');
            $table->char('currency', 3)->default('INR');
            $table->timestamp('expected_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Constraints
            $table->unique(['vendor_order_id', 'product_sku_id']);

            // Indexes
            $table->index('vendor_order_id');
            $table->index(['product_sku_id', 'created_at']);
            $table->index('sku_code_snapshot');
            $table->index('expected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_order_items');
    }
};
