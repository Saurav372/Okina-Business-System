<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('user_type', 32)->default('staff')->after('password')->index();
            $table->string('status', 32)->default('invited')->after('user_type')->index();
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('status');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->timestamp('last_login_at')->nullable()->after('locked_until');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->timestamp('password_changed_at')->nullable()->after('last_login_ip');
            $table->text('two_factor_secret')->nullable()->after('password_changed_at');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->timestamp('disabled_at')->nullable()->after('remember_token');
            $table->foreignId('disabled_by')->nullable()->after('disabled_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('disabled_by');
            $table->dropColumn([
                'user_type',
                'status',
                'failed_login_attempts',
                'locked_until',
                'last_login_at',
                'last_login_ip',
                'password_changed_at',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'disabled_at',
            ]);
        });
    }
};
