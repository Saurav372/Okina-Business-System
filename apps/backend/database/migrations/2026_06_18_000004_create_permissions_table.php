<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 160)->unique();
            $table->string('group', 80)->index();
            $table->string('guard_name', 80)->default('web')->index();
            $table->string('description', 300)->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->timestamps();

            $table->index(['guard_name', 'slug']);
            $table->index(['group', 'is_sensitive']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
