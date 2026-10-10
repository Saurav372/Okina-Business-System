<?php

namespace Tests\Feature;

use App\Models\LandingLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_successfully(): void
    {
        $response = $this->get('/lp/custom-t-shirts?source=meta');

        $response->assertStatus(200);
        $response->assertSee('Custom T-Shirts &amp; Branded Apparel for Businesses', false);
        $response->assertSee('Get Quote on WhatsApp');
        $response->assertSee('Pan-India Delivery');
        $response->assertSee('Low MOQ Options');
        $response->assertSee('DTF Printing');
        $response->assertSee('Embroidery');
        $response->assertSee('Need Pricing? Every Order is Custom.');
        $response->assertSee('How Ordering Works');
    }

    public function test_can_submit_quote_request(): void
    {
        $payload = [
            'name' => 'Amit Verma',
            'phone' => '9876543210',
            'product_type' => 'Company Uniforms',
            'quantity_range' => '26–100',
            'delivery_date' => '2026-11-20',
            'source' => 'meta',
            'utm_source' => 'meta_ad',
            'utm_campaign' => 'delhi_uniforms',
        ];

        $response = $this->post('/lp/quote-request', $payload);

        $response->assertSessionHas('quote_success', true);
        $this->assertDatabaseHas('landing_leads', [
            'name' => 'Amit Verma',
            'phone' => '9876543210',
            'product_type' => 'Company Uniforms',
            'quantity_range' => '26–100',
            'delivery_date' => '2026-11-20',
            'source' => 'meta',
            'status' => 'new',
        ]);
    }

    public function test_can_submit_quote_request_via_ajax_json(): void
    {
        $payload = [
            'name' => 'Priya Sharma',
            'phone' => '9123456780',
            'product_type' => 'Custom T-Shirts',
            'quantity_range' => '101–500',
        ];

        $response = $this->postJson('/lp/quote-request', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure(['success', 'message', 'lead_id', 'whatsapp_url']);
    }

    public function test_validation_fails_when_name_or_phone_missing(): void
    {
        $response = $this->post('/lp/quote-request', [
            'product_type' => 'Custom T-Shirts',
        ]);

        $response->assertSessionHasErrors(['name', 'phone']);
    }

    public function test_bulk_printing_landing_page_renders(): void
    {
        $response = $this->get('/lp/bulk-printing');

        $response->assertStatus(200);
        $response->assertSee('Get Bulk Printing for Your Business');
        $response->assertSee('printing_requirement');
        $response->assertSee('business_type');
    }

    public function test_bulk_printing_thank_you_page_renders(): void
    {
        $response = $this->get('/lp/bulk-printing/thank-you');

        $response->assertStatus(200);
        $response->assertSee('Thank You for Your Enquiry!');
    }

    public function test_bulk_printing_lead_submission_saves_lead_and_returns_json(): void
    {
        $payload = [
            'printing_requirement' => 'T-shirts',
            'business_type' => 'T-shirt / Clothing Business',
            'quantity' => '101–250 pieces',
            'design_readiness' => 'Design ready',
            'required_timeline' => 'Within 1–2 weeks',
            'full_name' => 'Rahul Verma',
            'phone' => '9876543210',
            'email' => 'rahul@example.com',
            'source' => 'okina-craft-landing-page',
        ];

        $response = $this->postJson('/lp/bulk-printing/quote', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('landing_leads', [
            'name' => 'Rahul Verma',
            'phone' => '9876543210',
            'email' => 'rahul@example.com',
            'product_type' => 'T-shirts',
            'business_type' => 'T-shirt / Clothing Business',
            'quantity_range' => '101–250 pieces',
            'design_readiness' => 'Design ready',
            'delivery_date' => 'Within 1–2 weeks',
            'source' => 'okina-craft-landing-page',
            'status' => 'new',
        ]);
    }

    public function test_bulk_printing_lead_submission_drops_honeypot_silently(): void
    {
        $payload = [
            'full_name' => 'Spam Bot',
            'phone' => '9876543210',
            'email' => 'bot@example.com',
            'website' => 'http://spam-link.com',
        ];

        $response = $this->postJson('/lp/bulk-printing/quote', $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('landing_leads', [
            'name' => 'Spam Bot',
        ]);
    }

    public function test_landing_pages_are_blocked_from_search_engine_indexing(): void
    {
        // 1. Check custom t-shirts page headers and meta tags
        $resCro = $this->get('/lp/custom-t-shirts');
        $resCro->assertStatus(200);
        $resCro->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
        $resCro->assertSee('<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">', false);
        $resCro->assertSee('<meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">', false);

        // 2. Check bulk printing page headers and meta tags
        $resBulk = $this->get('/lp/bulk-printing');
        $resBulk->assertStatus(200);
        $resBulk->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
        $resBulk->assertSee('<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">', false);
        $resBulk->assertSee('<meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">', false);

        // 3. Check bulk printing thank you page headers and meta tags
        $resThankYou = $this->get('/lp/bulk-printing/thank-you');
        $resThankYou->assertStatus(200);
        $resThankYou->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
        $resThankYou->assertSee('<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">', false);
        $resThankYou->assertSee('<meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">', false);

        // 4. Check robots.txt disallows /lp/
        $robotsRes = $this->get('/robots.txt');
        $robotsRes->assertStatus(200);
        $robotsRes->assertSee('Disallow: /lp/');
    }
}
