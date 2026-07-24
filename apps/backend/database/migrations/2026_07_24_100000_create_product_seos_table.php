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
        Schema::create('product_seos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique('product_id');

            // Search Engine Metadata
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('focus_keyword', 255)->nullable();
            $table->string('canonical_url', 255)->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);

            // Open Graph (Facebook / LinkedIn)
            $table->string('og_title', 255)->nullable();
            $table->text('og_description')->nullable();
            $table->foreignId('og_image_id')->nullable()->constrained('files')->nullOnDelete();

            // Twitter Card
            $table->string('twitter_title', 255)->nullable();
            $table->text('twitter_description')->nullable();
            $table->foreignId('twitter_image_id')->nullable()->constrained('files')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_seos');
    }
};
