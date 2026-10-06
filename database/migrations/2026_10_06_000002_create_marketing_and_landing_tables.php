<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Landing Pages Content Management
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('status')->default('published'); // published, draft
            $table->json('hero')->nullable();
            $table->json('showcase')->nullable();
            $table->json('use_cases')->nullable();
            $table->json('techniques')->nullable();
            $table->json('tagline')->nullable();
            $table->json('benefits')->nullable();
            $table->json('steps')->nullable();
            $table->json('gallery')->nullable();
            $table->json('faq')->nullable();
            $table->json('cta_settings')->nullable();
            $table->json('section_order')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        // 2. Marketing Tracking Settings (GTM, Meta Pixel, Meta CAPI, Event Mapping)
        Schema::create('marketing_tracking_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        // 3. Marketing Events (Source of Truth, Stream, Deduplication & Test Events)
        Schema::create('marketing_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->index();
            $table->string('event_name')->index(); // PageView, LandingPageView, WhatsAppClick, QuoteFormStart, QuoteFormSubmit, Lead, QualifiedLead, Purchase
            $table->string('source')->default('browser'); // browser, server
            $table->string('landing_page_slug')->nullable()->index();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('status')->default('received'); // received, forwarded_capi, failed_capi, test
            $table->json('payload')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_events');
        Schema::dropIfExists('marketing_tracking_settings');
        Schema::dropIfExists('landing_pages');
    }
};
