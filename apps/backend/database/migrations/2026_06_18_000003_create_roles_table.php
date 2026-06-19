<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('guard_name', 80)->default('web')->index();
            $table->string('description', 300)->nullable();
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['guard_name', 'slug']);
            $table->index(['is_system', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
