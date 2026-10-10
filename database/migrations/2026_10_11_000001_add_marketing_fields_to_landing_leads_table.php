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
        Schema::table('landing_leads', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->string('business_type')->nullable()->after('product_type');
            $table->string('design_readiness')->nullable()->after('quantity_range');
            $table->string('page_url')->nullable()->after('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_leads', function (Blueprint $table) {
            $table->dropColumn(['email', 'business_type', 'design_readiness', 'page_url']);
        });
    }
};
