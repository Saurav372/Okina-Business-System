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
        Schema::dropIfExists('order_cancellations');

        Schema::create('order_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('previous_status');
            $table->string('reason_code');
            $table->text('reason_note')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Production-stage loss tracking
            $table->boolean('material_consumed')->default(false);
            $table->boolean('customization_applied')->default(false);
            $table->boolean('scrap_incurred')->default(false);
            $table->unsignedInteger('affected_quantity')->nullable();
            $table->unsignedInteger('scrap_quantity')->nullable();
            $table->unsignedBigInteger('scrap_amount_minor')->nullable();
            $table->text('production_impact_notes')->nullable();

            // Shipping interception audit
            $table->boolean('physical_interception_confirmed')->default(false);
            $table->foreignId('shipping_intercept_confirmed_by_user_id')
                ->nullable()
                ->constrained('users', 'id', 'oc_ship_intercept_user_fk')
                ->nullOnDelete();
            $table->timestamp('shipping_intercept_confirmed_at')->nullable();
            $table->string('shipping_status_at_cancellation')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_cancellations');
    }
};
