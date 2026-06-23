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
        Schema::create('quotation_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->string('previous_status', 40);
            $table->string('quotation_type', 40);
            $table->date('valid_until')->nullable();
            $table->text('customer_note')->nullable();

            $table->unsignedBigInteger('subtotal_amount_minor');
            $table->unsignedBigInteger('discount_amount_minor');
            $table->unsignedBigInteger('shipping_amount_minor');
            $table->unsignedBigInteger('tax_amount_minor');
            $table->unsignedBigInteger('total_amount_minor');
            $table->char('currency', 3)->default('INR');

            $table->json('items_snapshot');
            $table->json('customer_snapshot')->nullable();
            $table->string('reason', 180)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->nullable();

            // Constraints and Indexes
            $table->unique(['quotation_id', 'revision_number']);
            $table->index(['quotation_id', 'created_at']);
            $table->index(['created_by_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_revisions');
    }
};
