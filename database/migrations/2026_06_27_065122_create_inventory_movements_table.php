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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_sku_id')->constrained('product_skus')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();

            // The following columns are for vendor/purchase records.
            // Database-level foreign key constraints are omitted here because
            // the referenced tables (vendor_orders, vendor_order_items, purchase_stock_ins)
            // do not exist yet. They will be added during implementation of C2.2.
            $table->unsignedBigInteger('vendor_order_id')->nullable();
            $table->unsignedBigInteger('vendor_order_item_id')->nullable();
            $table->unsignedBigInteger('purchase_stock_in_id')->nullable();

            $table->string('movement_type', 40);
            $table->string('direction', 20);
            $table->integer('quantity');

            // Balance snapshots at the time of the movement
            $table->integer('before_on_hand_quantity')->nullable();
            $table->integer('after_on_hand_quantity')->nullable();
            $table->integer('before_reserved_quantity')->nullable();
            $table->integer('after_reserved_quantity')->nullable();
            $table->integer('before_available_quantity')->nullable();
            $table->integer('after_available_quantity')->nullable();

            $table->string('reason_code', 80)->nullable();
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Represents the business occurrence time, independent of DB creation time
            $table->timestamp('occurred_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for fast history retrievals
            $table->index(['product_sku_id', 'occurred_at']);
            $table->index(['inventory_item_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
