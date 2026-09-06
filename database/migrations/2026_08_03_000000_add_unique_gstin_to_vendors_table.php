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
        // 1. Convert empty string GSTINs to NULL
        DB::table('vendors')
            ->where('gstin', '')
            ->update(['gstin' => null]);

        // 2. Pre-flight check for duplicate non-null GSTIN records
        $duplicates = DB::table('vendors')
            ->select('gstin', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull('gstin')
            ->groupBy('gstin')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $duplicateList = $duplicates->pluck('gstin')->implode(', ');
            throw new RuntimeException("Cannot add unique constraint 'vendors_gstin_unique'. Pre-existing duplicate GSTIN values found: {$duplicateList}");
        }

        // 3. Add unique index to gstin column
        Schema::table('vendors', function (Blueprint $table) {
            $table->unique('gstin', 'vendors_gstin_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropUnique('vendors_gstin_unique');
        });
    }
};
