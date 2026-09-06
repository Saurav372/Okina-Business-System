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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->foreignId('expense_category_id')
                ->constrained('expense_categories')
                ->restrictOnDelete();
            $table->bigInteger('amount_minor');
            $table->string('currency')->default('INR');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('reference')->nullable();
            $table->string('status')->default('draft');
            $table->date('occurred_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
