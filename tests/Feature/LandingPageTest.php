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
}
