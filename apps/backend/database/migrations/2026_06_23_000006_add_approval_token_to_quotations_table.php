<?php

use App\Models\Quotation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
                ->after('public_id');
        });

        // Backfill existing records
        Quotation::whereNull('approval_token')
            ->each(fn ($quote) => $quote->update(['approval_token' => Str::random(40)]));
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('approval_token');
        });
    }
};
