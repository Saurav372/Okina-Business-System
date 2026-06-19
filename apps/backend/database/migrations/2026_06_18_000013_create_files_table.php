<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 40)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('uploaded_by_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('storage_disk', 60);
            $table->string('storage_path', 500);
            $table->string('original_filename', 255)->nullable();
            $table->string('stored_filename', 255);
            $table->string('extension', 20)->nullable();
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->char('checksum_sha256', 64)->nullable();
            $table->string('file_kind', 40);
            $table->string('visibility', 40);
            $table->string('status', 40)->default('active')->index();
            $table->string('scan_status', 40)->default('skipped')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('protected_until')->nullable();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['storage_disk', 'storage_path']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['uploaded_by_user_id', 'created_at']);
            $table->index(['uploaded_by_customer_id', 'created_at']);
            $table->index(['file_kind', 'status', 'created_at']);
            $table->index(['visibility', 'status']);
            $table->index('checksum_sha256');
            $table->index('deleted_at');
            $table->index('protected_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
