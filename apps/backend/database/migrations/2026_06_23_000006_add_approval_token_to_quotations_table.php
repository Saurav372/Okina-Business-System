<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('approval_token', 80)
                ->nullable()
                ->unique()
                ->index()
                ->after('public_id');
        });

        // Backfill existing records safely
        DB::table('quotations')
            ->whereNull('approval_token')
            ->orderBy('id')
            ->chunkById(100, function ($quotes) {
                foreach ($quotes as $quote) {
                    DB::table('quotations')
                        ->where('id', $quote->id)
                        ->update([
                            'approval_token' => Str::random(40),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('approval_token');
        });
    }
};
