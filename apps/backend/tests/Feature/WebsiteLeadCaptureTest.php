<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteLeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guest users can successfully submit a bulk enquiry.
     */
    public function test_guest_can_submit_bulk_enquiry(): void
    {
        $payload = [
            'contact_name' => 'Saurav Sen',
            'email' => 'saurav@example.com',
            'phone' => '+919999999999',
            'interest_summary' => 'Inquiry for 500 custom prints',
            'requirements' => 'Needs express delivery options',
            'product_interest' => ['Custom T-Shirts', 'Printed Hoodies'],
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'bulk_print_2026',
            'utm_content' => 'ad_text_1',
            'utm_term' => 'custom print',
            'referrer_url' => 'https://www.google.com',
            'landing_page_url' => 'https://okinacraft.com/bulk-enquiry',
        ];

        $response = $this->postJson(route('api.catalog.leads.store'), $payload);

        $response->assertStatus(201);

        // Verify public-safe response structure (trimmed to exclude internal IDs and UTM/attribution fields)
        $response->assertJsonStructure([
            'public_id',
            'source',
            'status',
            'priority',
            'contact_name',
            'email',
            'phone',
            'interest_summary',
            'created_at',
            'updated_at',
        ]);

        $response->assertJsonMissing([
            'id',
            'created_by_user_id',
            'assigned_to_user_id',
            'customer_id',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term',
            'referrer_url',
            'landing_page_url',
        ]);

        // Verify the database record has been created with forced enums and correct UTM data
        $lead = Lead::query()->first();
        $this->assertNotNull($lead);
        $this->assertSame('website_bulk_enquiry', $lead->source);
        $this->assertSame('new', $lead->status);
        $this->assertSame('normal', $lead->priority);
        $this->assertSame('Saurav Sen', $lead->contact_name);
        $this->assertSame('saurav@example.com', $lead->email);
        $this->assertSame('google', $lead->utm_source);
        $this->assertSame('cpc', $lead->utm_medium);
        $this->assertSame('bulk_print_2026', $lead->utm_campaign);
        $this->assertSame('ad_text_1', $lead->utm_content);
        $this->assertSame('custom print', $lead->utm_term);
        $this->assertSame('https://www.google.com', $lead->referrer_url);
        $this->assertSame('https://okinacraft.com/bulk-enquiry', $lead->landing_page_url);
    }

    /**
     * Test validation rules for public submissions.
     */
    public function test_validation_fails_for_missing_required_fields(): void
    {
        // 1. Missing contact name and email/phone
        $response = $this->postJson(route('api.catalog.leads.store'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['contact_name', 'email', 'phone']);

        // 2. Validation fails for invalid email
        $response = $this->postJson(route('api.catalog.leads.store'), [
            'contact_name' => 'John Doe',
            'email' => 'not-an-email',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);

        // 3. Validation fails for nested array sizing in product interest
        $response = $this->postJson(route('api.catalog.leads.store'), [
            'contact_name' => 'John Doe',
            'email' => 'john@example.com',
            'product_interest' => [str_repeat('a', 130)], // Element exceeds 120 chars
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_interest.0']);

        // 4. Validation fails for invalid attribution URLs
        $response = $this->postJson(route('api.catalog.leads.store'), [
            'contact_name' => 'John Doe',
            'email' => 'john@example.com',
            'referrer_url' => 'not-a-url',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['referrer_url']);
    }

    /**
     * Test duplicate submissions are blocked within 5 minutes.
     */
    public function test_duplicate_submission_blocked_within_five_minutes(): void
    {
        $payload = [
            'contact_name' => 'Saurav Sen',
            'email' => 'saurav@example.com',
            'phone' => '+919999999999',
            'product_interest' => ['Custom T-Shirts'],
        ];

        // First request: success
        $this->postJson(route('api.catalog.leads.store'), $payload)
            ->assertStatus(201);

        // Second request (identical email, phone, product_interest) within 5 minutes: blocked
        $response = $this->postJson(route('api.catalog.leads.store'), $payload);
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'A duplicate enquiry was recently submitted. Please wait 5 minutes before submitting again.',
        ]);
        $response->assertJsonValidationErrors(['duplicate']);

        $this->assertSame(1, Lead::count());
    }

    /**
     * Test that submissions with different product interests are not blocked.
     */
    public function test_submission_with_different_product_interest_allowed(): void
    {
        $payload1 = [
            'contact_name' => 'Saurav Sen',
            'email' => 'saurav@example.com',
            'phone' => '+919999999999',
            'product_interest' => ['Custom T-Shirts'],
        ];

        $payload2 = [
            'contact_name' => 'Saurav Sen',
            'email' => 'saurav@example.com',
            'phone' => '+919999999999',
            'product_interest' => ['Printed Hoodies'], // Different product
        ];

        $this->postJson(route('api.catalog.leads.store'), $payload1)
            ->assertStatus(201);

        $this->postJson(route('api.catalog.leads.store'), $payload2)
            ->assertStatus(201);

        $this->assertSame(2, Lead::count());
    }

    /**
     * Test duplicate checking logic handles email/phone combinations robustly.
     */
    public function test_duplicate_checking_matches_only_strict_rules(): void
    {
        // Setup initial lead having both email and phone
        Lead::create([
            'source' => 'website_bulk_enquiry',
            'status' => 'new',
            'contact_name' => 'Saurav Sen',
            'email' => 'saurav@example.com',
            'phone' => '1234567890',
            'product_interest' => ['Custom T-Shirts'],
        ]);

        // Submission with same email but different phone should not be blocked (potential different contact)
        $payload1 = [
            'contact_name' => 'Saurav Sen',
            'email' => 'saurav@example.com',
            'phone' => '9999999999',
            'product_interest' => ['Custom T-Shirts'],
        ];
        $this->postJson(route('api.catalog.leads.store'), $payload1)
            ->assertStatus(201);

        // Submission with same phone but different email should not be blocked
        $payload2 = [
            'contact_name' => 'Saurav Sen',
            'email' => 'other@example.com',
            'phone' => '1234567890',
            'product_interest' => ['Custom T-Shirts'],
        ];
        $this->postJson(route('api.catalog.leads.store'), $payload2)
            ->assertStatus(201);
    }

    /**
     * Test duplicate submissions are allowed after 5 minutes.
     */
    public function test_submission_after_five_minutes_allowed(): void
    {
        $payload = [
            'contact_name' => 'Saurav Sen',
            'email' => 'saurav@example.com',
            'phone' => '+919999999999',
            'product_interest' => ['Custom T-Shirts'],
        ];

        $this->postJson(route('api.catalog.leads.store'), $payload)
            ->assertStatus(201);

        // Manually adjust the created_at of the first lead to be 6 minutes in the past
        $lead = Lead::query()->first();
        $lead->created_at = now()->subMinutes(6);
        $lead->save();

        // Resubmitting after 5 minutes: success
        $this->postJson(route('api.catalog.leads.store'), $payload)
            ->assertStatus(201);

        $this->assertSame(2, Lead::count());
    }

    /**
     * Test that source and status are forced by the server, ignoring request overrides.
     */
    public function test_source_and_status_are_forced_by_server(): void
    {
        $payload = [
            'contact_name' => 'Saurav Sen',
            'email' => 'saurav@example.com',
            'source' => 'manual', // Attempts to override source
            'status' => 'won',    // Attempts to override status
            'priority' => 'urgent', // Attempts to override priority (but priority defaults to normal)
            'created_by_user_id' => 999, // Attempts to override creator
        ];

        $response = $this->postJson(route('api.catalog.leads.store'), $payload);

        $response->assertStatus(201);

        // Check response attributes
        $response->assertJsonFragment([
            'source' => 'website_bulk_enquiry',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        // Check database row details
        $lead = Lead::query()->first();
        $this->assertNotNull($lead);
        $this->assertSame('website_bulk_enquiry', $lead->source);
        $this->assertSame('new', $lead->status);
        $this->assertSame('normal', $lead->priority);
        $this->assertNull($lead->created_by_user_id);
    }

    /**
     * Test that invalid or too long attribution fields are rejected.
     */
    public function test_validation_fails_for_invalid_or_long_attribution_fields(): void
    {
        // Test too long UTM fields
        $response = $this->postJson(route('api.catalog.leads.store'), [
            'contact_name' => 'John Doe',
            'email' => 'john@example.com',
            'utm_source' => str_repeat('a', 121),
            'utm_medium' => str_repeat('b', 121),
            'utm_campaign' => str_repeat('c', 161),
            'utm_content' => str_repeat('d', 161),
            'utm_term' => str_repeat('e', 161),
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term',
        ]);

        // Test invalid and too long URL fields
        $response = $this->postJson(route('api.catalog.leads.store'), [
            'contact_name' => 'John Doe',
            'email' => 'john@example.com',
            'referrer_url' => 'http://'.str_repeat('a', 2049),
            'landing_page_url' => 'http://'.str_repeat('b', 2049),
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'referrer_url',
            'landing_page_url',
        ]);

        $response = $this->postJson(route('api.catalog.leads.store'), [
            'contact_name' => 'John Doe',
            'email' => 'john@example.com',
            'landing_page_url' => 'not-a-url',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['landing_page_url']);
    }

    /**
     * Test public API response does not expose internal IDs.
     */
    public function test_public_api_response_does_not_expose_internal_ids(): void
    {
        $payload = [
            'contact_name' => 'Saurav Sen',
            'email' => 'saurav@example.com',
        ];

        $response = $this->postJson(route('api.catalog.leads.store'), $payload);

        $response->assertStatus(201);

        $lead = Lead::query()->first();
        $this->assertNotNull($lead);

        // Ensure database IDs and user association fields do not leak
        $response->assertJsonMissing([
            'id' => $lead->id,
            'customer_id' => $lead->customer_id,
            'assigned_to_user_id' => $lead->assigned_to_user_id,
            'created_by_user_id' => $lead->created_by_user_id,
        ]);
    }
}
