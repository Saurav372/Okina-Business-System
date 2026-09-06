<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('public_id', 40)->nullable()->unique()->after('id');
            $table->string('customer_type', 32)->default('individual')->after('public_id')->index();
            $table->string('first_name', 100)->nullable()->after('customer_type');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('display_name', 180)->nullable()->after('last_name')->index();
            $table->string('company_name', 180)->nullable()->after('display_name');
            $table->string('whatsapp_phone', 30)->nullable()->after('phone');
            $table->string('status', 32)->default('active')->after('whatsapp_phone')->index();
            $table->string('source', 40)->nullable()->after('status')->index();
            $table->boolean('accepts_marketing')->default(false)->after('source');
            $table->timestamp('email_verified_at')->nullable()->after('accepts_marketing');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->timestamp('last_login_at')->nullable()->after('phone_verified_at');
            $table->foreignId('created_by_user_id')->nullable()->after('last_login_at')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('merged_into_customer_id')->nullable()->after('updated_by_user_id')->constrained('customers')->nullOnDelete();
            $table->softDeletes();

            $table->index(['customer_type', 'status']);
            $table->index(['status', 'deleted_at']);
            $table->index('created_by_user_id');
            $table->index('updated_by_user_id');
            $table->index('merged_into_customer_id');
        });

        DB::table('customers')
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunkById(100, function ($customers): void {
                foreach ($customers as $customer) {
                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update([
                            'public_id' => 'CUS-'.str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT),
                            'customer_type' => 'individual',
                            'display_name' => $customer->display_name ?: ($customer->name ?: 'Customer '.$customer->id),
                            'status' => $customer->status ?: 'active',
                            'source' => $customer->source ?: 'website',
                            'accepts_marketing' => $customer->accepts_marketing ?? false,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('merged_into_customer_id');
            $table->dropConstrainedForeignId('updated_by_user_id');
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropColumn([
                'public_id',
                'customer_type',
                'first_name',
                'last_name',
                'display_name',
                'company_name',
                'whatsapp_phone',
                'status',
                'source',
                'accepts_marketing',
                'email_verified_at',
                'phone_verified_at',
                'last_login_at',
                'deleted_at',
            ]);
        });
    }
};
