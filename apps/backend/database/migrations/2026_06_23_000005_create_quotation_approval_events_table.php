<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_approval_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')
                ->constrained('quotations')
                ->cascadeOnDelete();

            $table->string('event_type', 40); // approved, rejected, revision_requested, cancelled, sent, revised, expired, converted
            $table->unsignedInteger('revision_number');
            $table->string('actor_type', 40); // customer, staff, system

            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('actor_customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->string('actor_name_snapshot', 160)->nullable();
            $table->string('actor_email_snapshot', 180)->nullable();
            $table->text('note')->nullable();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->timestamp('occurred_at');
            $table->timestamps();

            // Indexes per crm-quotations-schema.md
            $table->index(['quotation_id', 'occurred_at']);
            $table->index(['event_type', 'occurred_at']);
            $table->index(['actor_user_id', 'occurred_at']);
            $table->index(['actor_customer_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_approval_events');
    }
};
