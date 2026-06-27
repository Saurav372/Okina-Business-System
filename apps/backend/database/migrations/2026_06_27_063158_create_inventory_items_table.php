<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_sku_id')->unique()->constrained('product_skus')->cascadeOnDelete();
            $table->integer('on_hand_quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('available_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->nullable();
            $table->boolean('allow_negative_stock')->default(false);
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();
        });

        // Add CHECK constraints in MySQL
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE inventory_items ADD CONSTRAINT chk_reserved_quantity CHECK (reserved_quantity >= 0)');
            DB::statement('ALTER TABLE inventory_items ADD CONSTRAINT chk_available_quantity CHECK (available_quantity = on_hand_quantity - reserved_quantity)');
        }

        DB::table('product_skus')->orderBy('id')->chunk(100, function ($skus) {
            $inserts = [];
            foreach ($skus as $sku) {
                $inserts[] = [
                    'product_sku_id' => $sku->id,
                    'on_hand_quantity' => 0,
                    'reserved_quantity' => 0,
                    'available_quantity' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (! empty($inserts)) {
                DB::table('inventory_items')->insert($inserts);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
