<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('quotation_id')
                ->constrained('quotations')
                ->cascadeOnDelete();

            $table->foreignId('product_sku_id')
                ->nullable()
                ->constrained('product_skus')
                ->restrictOnDelete();

            $table->unsignedBigInteger('product_id_snapshot')->nullable();
            $table->string('product_name_snapshot', 180)->nullable();
            $table->string('sku_code_snapshot', 80)->nullable();

            $table->string('item_name', 180);
            $table->json('selected_options_snapshot')->nullable();
            $table->json('customization_snapshot')->nullable();

            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_minor');
            $table->unsignedBigInteger('discount_amount_minor')->default(0);
            $table->unsignedBigInteger('tax_amount_minor')->default(0);
            $table->unsignedBigInteger('line_subtotal_minor');
            $table->unsignedBigInteger('line_total_minor');
            $table->char('currency', 3)->default('INR');

            $table->unsignedInteger('sort_order')->default(0);

            $table->text('customer_note')->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamps();

            // Indexes per crm-quotations-schema.md
            $table->index(['quotation_id', 'sort_order']);
            $table->index(['product_sku_id']);
            $table->index(['product_id_snapshot']);
            $table->index(['sku_code_snapshot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
