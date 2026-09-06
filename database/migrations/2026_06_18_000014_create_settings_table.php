<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group_name', 60);
            $table->string('key', 120);
            $table->json('value')->nullable();
            $table->string('value_type', 30)->default('string');
            $table->string('description', 255)->nullable();
            $table->boolean('is_secret')->default(false);
            $table->timestamps();

            $table->unique(['group_name', 'key']);
            $table->index(['group_name', 'key']);
            $table->index(['group_name', 'value_type']);
            $table->index(['group_name', 'is_secret']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
