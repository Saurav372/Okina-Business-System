<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('design_status', 32)->default('under_review')->index();
            $table->text('design_issue_message')->nullable();
            $table->string('production_status', 32)->default('not_started')->index();
            $table->string('shipping_status', 32)->default('not_shipped')->index();
            $table->timestamp('ready_to_ship_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('courier_name', 100)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->text('tracking_url')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->text('cancellation_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'design_status',
                'design_issue_message',
                'production_status',
                'shipping_status',
                'ready_to_ship_at',
                'shipped_at',
                'delivered_at',
                'courier_name',
                'tracking_number',
                'tracking_url',
                'estimated_delivery_at',
                'cancellation_reason',
            ]);
        });
    }
};
