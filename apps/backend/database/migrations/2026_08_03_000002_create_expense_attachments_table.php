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
        Schema::create('expense_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 32)->unique('expense_attachments_public_id_unique');
            $table->foreignId('expense_id')
                ->unique('expense_attachments_expense_id_unique')
                ->constrained('expenses')
                ->cascadeOnDelete();
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('disk')->default('local');
            $table->string('mime_type');
            $table->bigInteger('size_bytes');
            $table->string('checksum', 64);
            $table->foreignId('uploaded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('uploaded_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_attachments');
    }
};
