<?php

use App\Enums\LeadFollowUpStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_follow_ups', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('lead_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('assigned_to_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status', 32)
                ->default(LeadFollowUpStatus::PENDING->value);

            $table->timestamp('due_at');
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('completed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('snoozed_until')->nullable();
            $table->string('subject', 180)->nullable();
            $table->text('notes')->nullable();
            $table->string('notification_key', 120)->unique()->nullable();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Composite indexes for follow-ups query optimization
            $table->index(['lead_id', 'due_at']);
            $table->index(['assigned_to_user_id', 'status', 'due_at']);
            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_follow_ups');
    }
};
