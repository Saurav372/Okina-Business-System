<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 40)->unique();

            // Ownership and assignment
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Source and classification
            $table->string('source', 40);
            $table->string('source_detail', 160)->nullable();
            $table->string('status', 40);
            $table->string('priority', 20)->default('normal');

            // Raw contact info
            $table->string('contact_name', 160)->nullable();
            $table->string('company_name', 180)->nullable();
            $table->string('email', 180)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->char('country_code', 2)->default('IN');

            // Enquiry content
            $table->string('interest_summary', 300)->nullable();
            $table->text('requirements')->nullable();
            $table->json('product_interest')->nullable();

            // UTM attribution
            $table->string('utm_source', 120)->nullable();
            $table->string('utm_medium', 120)->nullable();
            $table->string('utm_campaign', 160)->nullable();
            $table->string('utm_content', 160)->nullable();
            $table->string('utm_term', 160)->nullable();

            // Page attribution
            $table->text('referrer_url')->nullable();
            $table->text('landing_page_url')->nullable();

            // Lifecycle timestamps
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->string('lost_reason', 160)->nullable();
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for CRM queues and reporting
            $table->index(['status', 'created_at']);
            $table->index(['assigned_to_user_id', 'status', 'created_at']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['source', 'created_at']);
            $table->index(['priority', 'status', 'created_at']);
            $table->index(['last_contacted_at']);
            $table->index(['deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
