<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 40)->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('sku_id')->constrained('product_skus')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('product_name_snapshot', 180);
            $table->string('product_slug_snapshot', 200);
            $table->string('sku_code_snapshot', 80);
            $table->char('customization_fingerprint', 64);
            $table->json('customization_snapshot');
            $table->unsignedBigInteger('unit_price_minor');
            $table->unsignedBigInteger('line_subtotal_minor');
            $table->unsignedBigInteger('line_total_minor');
            $table->char('currency', 3)->default('INR');
            $table->string('price_source', 32)->default('unpriced');
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
            $table->index(['sku_id', 'created_at']);
            $table->index(['sku_code_snapshot']);
            $table->index(['customization_fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
