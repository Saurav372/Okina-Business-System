<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('address_type', 32)->default('shipping')->index();
            $table->string('label', 80)->nullable();
            $table->string('contact_name', 160);
            $table->string('phone', 30);
            $table->string('company_name', 180)->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('address_line_1', 180);
            $table->string('address_line_2', 180)->nullable();
            $table->string('landmark', 160)->nullable();
            $table->string('city', 120);
            $table->string('state', 120);
            $table->string('postal_code', 20)->index();
            $table->char('country_code', 2)->default('IN');
            $table->boolean('is_default_shipping')->default(false);
            $table->boolean('is_default_billing')->default(false);
            $table->string('delivery_notes', 300)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'deleted_at']);
            $table->index(['customer_id', 'is_default_shipping']);
            $table->index(['customer_id', 'is_default_billing']);
            $table->index(['city', 'state']);
            $table->index('created_by_user_id');
            $table->index('updated_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
