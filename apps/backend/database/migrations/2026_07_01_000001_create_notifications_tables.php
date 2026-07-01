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
        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('template_key', 120);
            $table->string('channel', 40);
            $table->string('name', 180);
            $table->string('subject_template', 300)->nullable();
            $table->text('body_template');
            $table->string('locale', 12)->default('en');
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->json('allowed_variables')->nullable();
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            // Indexes and Unique Constraints
            $table->unique(['template_key', 'channel', 'locale', 'version'], 'nt_key_channel_locale_version_unique');
            $table->index(['template_key', 'channel', 'locale', 'status'], 'nt_lookup_index');
            $table->index(['template_key', 'status'], 'nt_key_status_index');
            $table->index(['channel', 'status'], 'nt_channel_status_index');
        });

        Schema::create('notification_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 120);
            $table->foreignId('template_id')
                ->nullable()
                ->constrained('notification_templates')
                ->nullOnDelete();
            $table->string('template_key', 120)->nullable();
            $table->unsignedInteger('template_version')->nullable();
            $table->string('channel', 40);
            $table->string('status', 40)->default('pending');
            $table->string('recipient_type', 40);
            $table->foreignId('recipient_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('recipient_customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();
            $table->string('recipient_address', 255)->nullable();
            $table->string('subject_rendered', 300)->nullable();
            $table->text('body_summary')->nullable();
            $table->json('payload')->nullable();
            $table->string('related_type', 120)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('dedupe_key', 160)->unique()->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['event_type', 'created_at']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['channel', 'status', 'created_at']);
            $table->index(['recipient_user_id', 'created_at']);
            $table->index(['recipient_customer_id', 'created_at']);
            $table->index(['related_type', 'related_id']);
        });

        Schema::create('notification_delivery_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_log_id')
                ->constrained('notification_logs')
                ->cascadeOnDelete();
            $table->string('status', 40);
            $table->string('provider_reference', 180)->nullable();
            $table->text('error_message')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();

            // Indexes
            $table->index(['notification_log_id', 'attempted_at'], 'nda_log_time_index');
        });

        // Add check constraints in MySQL
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE notification_templates ADD CONSTRAINT chk_nt_channel CHECK (channel IN ('email', 'sms', 'whatsapp', 'database'))");
            DB::statement("ALTER TABLE notification_templates ADD CONSTRAINT chk_nt_status CHECK (status IN ('draft', 'active', 'inactive'))");
            DB::statement('ALTER TABLE notification_templates ADD CONSTRAINT chk_nt_version CHECK (version >= 1)');

            DB::statement("ALTER TABLE notification_logs ADD CONSTRAINT chk_nl_channel CHECK (channel IN ('email', 'sms', 'whatsapp', 'database'))");
            DB::statement("ALTER TABLE notification_logs ADD CONSTRAINT chk_nl_status CHECK (status IN ('pending', 'queued', 'sent', 'failed', 'cancelled', 'skipped'))");
            DB::statement("ALTER TABLE notification_logs ADD CONSTRAINT chk_nl_recipient_type CHECK (recipient_type IN ('customer', 'user', 'external'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_delivery_attempts');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
    }
};
