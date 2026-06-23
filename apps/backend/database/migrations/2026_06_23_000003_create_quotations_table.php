<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 40)->unique();
            $table->string('quotation_type', 40);
            $table->string('status', 40)->default('draft');

            $table->foreignId('lead_id')
                ->nullable()
                ->constrained('leads')
                ->nullOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->foreignId('assigned_to_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('converted_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('converted_order_id')
                ->nullable()
                ->unique()
                ->constrained('orders')
                ->nullOnDelete();

            $table->json('customer_snapshot')->nullable();

            $table->unsignedBigInteger('subtotal_amount_minor')->default(0);
            $table->unsignedBigInteger('discount_amount_minor')->default(0);
            $table->unsignedBigInteger('shipping_amount_minor')->default(0);
            $table->unsignedBigInteger('tax_amount_minor')->default(0);
            $table->unsignedBigInteger('total_amount_minor')->default(0);
            $table->char('currency', 3)->default('INR');

            $table->unsignedInteger('current_revision_number')->default(1);
            $table->date('valid_until')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->string('conversion_idempotency_key', 120)->nullable()->unique();

            $table->text('customer_note')->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamps();

            // Indexes per crm-quotations-schema.md
            $table->index(['lead_id', 'created_at']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['assigned_to_user_id', 'status', 'created_at']);
            $table->index(['status', 'valid_until']);
            $table->index(['quotation_type', 'status', 'created_at']);
            $table->index(['sent_at']);
            $table->index(['approved_at']);
            $table->index(['converted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
