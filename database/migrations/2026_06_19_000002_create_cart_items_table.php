<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 40)->unique();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('sku_id')->constrained('product_skus')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('product_name_snapshot', 180);
            $table->string('product_slug_snapshot', 200);
            $table->string('sku_code_snapshot', 80);
            $table->char('customization_fingerprint', 64);
            $table->json('customization_snapshot');
            $table->timestamps();

            $table->unique(['cart_id', 'sku_id', 'customization_fingerprint']);
            $table->index(['cart_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
            $table->index(['sku_id', 'created_at']);
            $table->index(['sku_code_snapshot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
