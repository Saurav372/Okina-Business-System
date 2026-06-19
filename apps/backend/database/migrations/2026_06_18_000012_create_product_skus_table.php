<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_skus', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('sku_code', 80)->unique();
            $table->string('variant_key', 500);
            $table->json('option_values');
            $table->string('name_suffix', 180)->nullable();
            $table->string('barcode', 120)->nullable()->unique();
            $table->string('status', 32)->default('active')->index();
            $table->boolean('direct_checkout_enabled')->default(true);
            $table->boolean('quote_required')->default(false);
            $table->boolean('track_stock')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->nullable();
            $table->boolean('allow_backorder')->default(false);
            $table->unsignedInteger('price_minor')->nullable();
            $table->unsignedInteger('compare_at_price_minor')->nullable();
            $table->unsignedInteger('weight_grams')->nullable();
            $table->unsignedInteger('length_mm')->nullable();
            $table->unsignedInteger('width_mm')->nullable();
            $table->unsignedInteger('height_mm')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'variant_key']);
            $table->index(['product_id', 'status', 'sort_order']);
            $table->index(['status', 'direct_checkout_enabled']);
            $table->index(['track_stock', 'stock_quantity']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_skus');
    }
};
