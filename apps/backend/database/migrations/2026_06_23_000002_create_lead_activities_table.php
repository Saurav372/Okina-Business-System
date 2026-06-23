<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();

            $table->string('activity_type', 40);
            $table->string('subject', 180)->nullable();
            $table->text('body')->nullable();
            $table->json('metadata')->nullable();

            $table->boolean('customer_visible')->default(false);

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            // Indexes per crm-quotations-schema.md
            $table->index(['lead_id', 'occurred_at']);
            $table->index(['activity_type', 'occurred_at']);
            $table->index(['created_by_user_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};
