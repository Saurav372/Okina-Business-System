<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->string('role', 20)->default('gallery'); // 'cover' | 'gallery'
            $table->string('alt_text', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            // A file can only be linked once per product
            $table->unique(['product_id', 'file_id']);
            $table->index(['product_id', 'sort_order']);
            $table->index(['product_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
