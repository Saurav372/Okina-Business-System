<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_code', 64)->unique();
            $table->foreignId('product_sku_id')->constrained('product_skus')->cascadeOnDelete();
            $table->string('source_location', 32)->default('main_warehouse');
            $table->string('destination_location', 32)->default('retail_store');
            $table->integer('quantity')->default(0);
            $table->string('status', 32)->default('draft');
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['source_location', 'destination_location']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfers');
    }
};
