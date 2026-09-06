<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id', 80)->unique();
            $table->string('action', 120);
            $table->string('module', 60);
            $table->string('actor_type', 40);
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('actor_customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();
            $table->string('actor_label_snapshot', 180)->nullable();
            $table->string('subject_type', 120);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_public_id', 80)->nullable();
            $table->string('summary', 300)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('request_id', 120)->nullable();
            $table->string('idempotency_key', 120)->unique()->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at');

            // Composite and single query indexes
            $table->index(['module', 'occurred_at']);
            $table->index(['action', 'occurred_at']);
            $table->index(['actor_type', 'actor_user_id', 'occurred_at']);
            $table->index(['actor_type', 'actor_customer_id', 'occurred_at']);
            $table->index(['subject_type', 'subject_id', 'occurred_at']);
            $table->index('subject_public_id');
            $table->index('request_id');
        });

        Schema::create('audit_log_related_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audit_log_id')
                ->constrained('audit_logs')
                ->cascadeOnDelete();
            $table->string('related_type', 120);
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('related_public_id', 80)->nullable();
            $table->string('relation', 60);
            $table->timestamp('created_at');

            // Indexes
            $table->index('audit_log_id');
            $table->index(['related_type', 'related_id']);
            $table->index('related_public_id');
        });

        // Add CHECK constraints in MySQL
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE audit_logs ADD CONSTRAINT chk_audit_actor_type CHECK (actor_type IN ('user', 'customer', 'system', 'job', 'provider'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log_related_records');
        Schema::dropIfExists('audit_logs');
    }
};
