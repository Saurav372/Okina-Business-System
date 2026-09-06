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
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasColumn('expenses', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable();
            }
            if (! Schema::hasColumn('expenses', 'submitted_by_user_id')) {
                $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('expenses', 'approved_by_user_id')) {
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('expenses', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable();
            }
            if (! Schema::hasColumn('expenses', 'rejected_by_user_id')) {
                $table->foreignId('rejected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('expenses', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
            if (! Schema::hasColumn('expenses', 'withdrawn_at')) {
                $table->timestamp('withdrawn_at')->nullable();
            }
            if (! Schema::hasColumn('expenses', 'withdrawn_by_user_id')) {
                $table->foreignId('withdrawn_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('expenses', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['submitted_by_user_id']);
            $table->dropForeign(['approved_by_user_id']);
            $table->dropForeign(['rejected_by_user_id']);
            $table->dropForeign(['withdrawn_by_user_id']);
            $table->dropColumn([
                'submitted_at',
                'submitted_by_user_id',
                'approved_by_user_id',
                'rejected_at',
                'rejected_by_user_id',
                'rejection_reason',
                'withdrawn_at',
                'withdrawn_by_user_id',
                'metadata',
            ]);
        });
    }
};
