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
        Schema::create('google_sheets_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_class');
            $table->unsignedBigInteger('model_id');
            $table->string('unique_key');
            $table->string('unique_value');
            $table->enum('status', ['queued', 'processing', 'success', 'failed'])->default('queued');
            $table->integer('attempts')->default(0);
            $table->string('payload_hash', 64);
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->string('triggered_by')->default('automatic');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('job_uuid')->nullable();
            $table->string('connection')->nullable();
            $table->string('queue')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['model_class', 'model_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_sheets_sync_logs');
    }
};
