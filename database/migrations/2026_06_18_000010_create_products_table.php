<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('primary_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name', 180);
            $table->string('slug', 200)->unique();
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('product_type', 32)->default('simple')->index();
            $table->string('customization_mode', 32)->default('none')->index();
            $table->string('fulfillment_type', 32)->default('stocked')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->string('visibility', 32)->default('private')->index();
            $table->boolean('direct_checkout_enabled')->default(false);
            $table->boolean('quote_enabled')->default(true);
            $table->unsignedInteger('min_order_quantity')->default(1);
            $table->unsignedInteger('max_order_quantity')->nullable();
            $table->unsignedInteger('bulk_threshold_quantity')->nullable();
            $table->unsignedInteger('base_price_minor')->nullable();
            $table->char('currency', 3)->default('INR');
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'visibility', 'published_at']);
            $table->index(['primary_category_id', 'status', 'visibility']);
            $table->index(['sort_order', 'id']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
