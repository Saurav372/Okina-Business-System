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
        Schema::table('users', function (Blueprint $table) {
            $table->string('invitation_token_hash', 64)->nullable()->after('remember_token');
            $table->timestamp('invitation_sent_at')->nullable()->after('invitation_token_hash');
            $table->timestamp('invitation_expires_at')->nullable()->after('invitation_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'invitation_token_hash',
                'invitation_sent_at',
                'invitation_expires_at',
            ]);
        });
    }
};
