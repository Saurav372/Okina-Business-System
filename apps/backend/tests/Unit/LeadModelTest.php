<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadModelTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------ creation

    /**
     * Test a lead can be created with minimal required fields.
     */
    public function test_lead_can_be_created_with_minimal_fields(): void
    {
        $lead = Lead::factory()->create([
            'source' => 'manual',
            'contact_name' => 'Ravi Kumar',
            'phone' => '9876543210',
        ]);

        $this->assertNotNull($lead->id);
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'source' => 'manual']);
    }

    /**
     * Test factory generates a valid lead record.
     */
    public function test_factory_generates_valid_lead(): void
    {
        $lead = Lead::factory()->create();

        $this->assertNotNull($lead->id);
        $this->assertNotEmpty($lead->public_id);
        $this->assertNotEmpty($lead->source);
        $this->assertNotEmpty($lead->status);
        $this->assertNotEmpty($lead->priority);
        $this->assertNotEmpty($lead->country_code);
    }

    // ------------------------------------------------------------------ public_id

    /**
     * Test public_id is automatically generated on creation.
     */
    public function test_public_id_is_auto_generated(): void
    {
        $lead = Lead::factory()->create();

        $this->assertNotNull($lead->public_id);
        $this->assertStringStartsWith('LD-', $lead->public_id);
    }

    /**
     * Test two leads always receive different public_ids.
     */
    public function test_public_ids_are_unique_per_lead(): void
    {
        $first = Lead::factory()->create();
        $second = Lead::factory()->create();

        $this->assertNotSame($first->public_id, $second->public_id);
    }

    /**
     * Test public_id can be provided manually and is preserved.
     */
    public function test_public_id_is_preserved_when_provided(): void
    {
        $lead = Lead::factory()->create(['public_id' => 'LD-TESTID000001']);

        $this->assertSame('LD-TESTID000001', $lead->public_id);
    }

    // ------------------------------------------------------------------ defaults

    /**
     * Test status defaults to 'new' on creation.
     */
    public function test_status_defaults_to_new(): void
    {
        $lead = Lead::create([
            'source' => 'manual',
            'contact_name' => 'Test Contact',
            'phone' => '9000000000',
        ]);

        $this->assertSame('new', $lead->status);
    }

    /**
     * Test priority defaults to 'normal' on creation.
     */
    public function test_priority_defaults_to_normal(): void
    {
        $lead = Lead::create([
            'source' => 'phone',
            'contact_name' => 'Test Contact',
            'phone' => '9000000001',
        ]);

        $this->assertSame('normal', $lead->priority);
    }

    /**
     * Test country_code defaults to 'IN' on creation.
     */
    public function test_country_code_defaults_to_in(): void
    {
        $lead = Lead::create([
            'source' => 'email',
            'contact_name' => 'Test Contact',
            'email' => 'test@example.com',
        ]);

        $this->assertSame('IN', $lead->country_code);
    }

    // ------------------------------------------------------------------ enums / validation

    /**
     * Test each approved status value can be stored and retrieved.
     */
    public function test_all_approved_statuses_can_be_stored(): void
    {
        $statuses = ['new', 'assigned', 'contacted', 'qualified', 'quoted', 'won', 'lost', 'spam'];

        foreach ($statuses as $status) {
            $lead = Lead::factory()->create(['status' => $status]);
            $this->assertSame($status, $lead->fresh()->status, "Failed for status: {$status}");
        }
    }

    /**
     * Test each approved priority value can be stored and retrieved.
     */
    public function test_all_approved_priorities_can_be_stored(): void
    {
        $priorities = ['low', 'normal', 'high', 'urgent'];

        foreach ($priorities as $priority) {
            $lead = Lead::factory()->create(['priority' => $priority]);
            $this->assertSame($priority, $lead->fresh()->priority, "Failed for priority: {$priority}");
        }
    }

    /**
     * Test each approved source value can be stored and retrieved.
     */
    public function test_all_approved_sources_can_be_stored(): void
    {
        $sources = ['website_bulk_enquiry', 'manual', 'phone', 'whatsapp', 'email', 'referral', 'import'];

        foreach ($sources as $source) {
            $lead = Lead::factory()->create(['source' => $source]);
            $this->assertSame($source, $lead->fresh()->source, "Failed for source: {$source}");
        }
    }

    // ------------------------------------------------------------------ JSON / casts

    /**
     * Test product_interest is cast to array when present.
     */
    public function test_product_interest_is_cast_to_array(): void
    {
        $interest = [
            ['product_name' => 'Custom T-Shirt', 'quantity' => 100],
            ['product_name' => 'Polo Shirt', 'quantity' => 50],
        ];

        $lead = Lead::factory()->create(['product_interest' => $interest]);
        $fresh = $lead->fresh();

        $this->assertIsArray($fresh->product_interest);
        $this->assertCount(2, $fresh->product_interest);
        $this->assertSame('Custom T-Shirt', $fresh->product_interest[0]['product_name']);
    }

    /**
     * Test product_interest is null when not provided.
     */
    public function test_product_interest_is_nullable(): void
    {
        $lead = Lead::factory()->create(['product_interest' => null]);

        $this->assertNull($lead->fresh()->product_interest);
    }

    // ------------------------------------------------------------------ lifecycle timestamps

    /**
     * Test lifecycle timestamps are cast as datetime objects.
     */
    public function test_lifecycle_timestamps_are_cast_as_datetime(): void
    {
        $lead = Lead::factory()->create([
            'last_contacted_at' => now()->subDay(),
            'qualified_at' => now()->subHours(6),
            'lost_at' => null,
            'converted_at' => null,
        ]);

        $fresh = $lead->fresh();

        $this->assertInstanceOf(Carbon::class, $fresh->last_contacted_at);
        $this->assertInstanceOf(Carbon::class, $fresh->qualified_at);
        $this->assertNull($fresh->lost_at);
        $this->assertNull($fresh->converted_at);
    }

    // ------------------------------------------------------------------ soft delete

    /**
     * Test a lead can be soft-deleted and is excluded from default queries.
     */
    public function test_lead_can_be_soft_deleted(): void
    {
        $lead = Lead::factory()->create();
        $id = $lead->id;

        $lead->delete();

        $this->assertNull(Lead::find($id));
        $this->assertNotNull(Lead::withTrashed()->find($id));
        $this->assertNotNull(Lead::withTrashed()->find($id)->deleted_at);
    }

    /**
     * Test a soft-deleted lead can be restored.
     */
    public function test_soft_deleted_lead_can_be_restored(): void
    {
        $lead = Lead::factory()->create();
        $lead->delete();

        Lead::withTrashed()->find($lead->id)->restore();

        $this->assertNotNull(Lead::find($lead->id));
    }

    // ------------------------------------------------------------------ relations

    /**
     * Test a lead belongs to an assigned user.
     */
    public function test_lead_belongs_to_assigned_user(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->assignedTo($user)->create();

        $this->assertNotNull($lead->assignedTo);
        $this->assertSame($user->id, $lead->assignedTo->id);
    }

    /**
     * Test a lead can exist without an assigned user.
     */
    public function test_lead_can_exist_without_assigned_user(): void
    {
        $lead = Lead::factory()->create(['assigned_to_user_id' => null]);

        $this->assertNull($lead->assignedTo);
    }

    /**
     * Test a lead belongs to a creator user.
     */
    public function test_lead_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->createdBy($user)->create();

        $this->assertNotNull($lead->creator);
        $this->assertSame($user->id, $lead->creator->id);
    }

    /**
     * Test a lead can exist without a creator (system/website capture).
     */
    public function test_lead_can_exist_without_creator(): void
    {
        $lead = Lead::factory()->create(['created_by_user_id' => null]);

        $this->assertNull($lead->creator);
    }

    /**
     * Test a lead can be linked to a customer after qualification.
     */
    public function test_lead_belongs_to_customer_after_qualification(): void
    {
        $customer = Customer::factory()->create();
        $lead = Lead::factory()->qualified($customer)->create();

        $this->assertNotNull($lead->customer);
        $this->assertSame($customer->id, $lead->customer->id);
    }

    /**
     * Test a lead can exist without a customer link.
     */
    public function test_lead_can_exist_without_customer(): void
    {
        $lead = Lead::factory()->create(['customer_id' => null]);

        $this->assertNull($lead->customer);
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Test isOpen returns true for non-terminal statuses.
     */
    public function test_is_open_returns_true_for_active_statuses(): void
    {
        foreach (['new', 'assigned', 'contacted', 'qualified', 'quoted'] as $status) {
            $lead = Lead::factory()->create(['status' => $status]);
            $this->assertTrue($lead->isOpen(), "Expected isOpen() true for status: {$status}");
        }
    }

    /**
     * Test isOpen returns false for terminal statuses.
     */
    public function test_is_open_returns_false_for_terminal_statuses(): void
    {
        foreach (['won', 'lost', 'spam'] as $status) {
            $lead = Lead::factory()->create(['status' => $status]);
            $this->assertFalse($lead->isOpen(), "Expected isOpen() false for status: {$status}");
        }
    }

    /**
     * Test hasContactRoute returns true when email is present.
     */
    public function test_has_contact_route_true_when_email_present(): void
    {
        $lead = Lead::factory()->create([
            'email' => 'contact@example.com',
            'phone' => null,
            'customer_id' => null,
        ]);

        $this->assertTrue($lead->hasContactRoute());
    }

    /**
     * Test hasContactRoute returns true when phone is present.
     */
    public function test_has_contact_route_true_when_phone_present(): void
    {
        $lead = Lead::factory()->create([
            'email' => null,
            'phone' => '9876543210',
            'customer_id' => null,
        ]);

        $this->assertTrue($lead->hasContactRoute());
    }

    /**
     * Test hasContactRoute returns false when no contact info exists.
     */
    public function test_has_contact_route_false_when_no_contact_info(): void
    {
        $lead = Lead::factory()->create([
            'email' => null,
            'phone' => null,
            'customer_id' => null,
        ]);

        $this->assertFalse($lead->hasContactRoute());
    }

    // ------------------------------------------------------------------ route key

    /**
     * Test route model binding uses public_id.
     */
    public function test_route_key_name_is_public_id(): void
    {
        $lead = new Lead;

        $this->assertSame('public_id', $lead->getRouteKeyName());
    }

    // ------------------------------------------------------------------ factory states

    /**
     * Test the websiteBulkEnquiry factory state sets correct source and UTM fields.
     */
    public function test_website_bulk_enquiry_factory_state(): void
    {
        $lead = Lead::factory()->websiteBulkEnquiry()->create();

        $this->assertSame('website_bulk_enquiry', $lead->source);
    }

    /**
     * Test the lost factory state sets status and lost_at.
     */
    public function test_lost_factory_state(): void
    {
        $lead = Lead::factory()->lost()->create();

        $this->assertSame('lost', $lead->status);
        $this->assertNotNull($lead->lost_at);
    }

    /**
     * Test the won factory state sets status and converted_at.
     */
    public function test_won_factory_state(): void
    {
        $lead = Lead::factory()->won()->create();

        $this->assertSame('won', $lead->status);
        $this->assertNotNull($lead->converted_at);
    }

    /**
     * Test UTM and page attribution fields are preserved and serialized on the model level.
     */
    public function test_attribution_fields_are_preserved_and_serialized(): void
    {
        $lead = Lead::factory()->create([
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'campaign',
            'utm_content' => 'content',
            'utm_term' => 'term',
            'referrer_url' => 'https://referrer.com',
            'landing_page_url' => 'https://landing.com',
        ]);

        $serialized = $lead->toArray();

        // Verify they are preserved in serialized array output
        $this->assertSame('google', $serialized['utm_source'] ?? null);
        $this->assertSame('cpc', $serialized['utm_medium'] ?? null);
        $this->assertSame('campaign', $serialized['utm_campaign'] ?? null);
        $this->assertSame('content', $serialized['utm_content'] ?? null);
        $this->assertSame('term', $serialized['utm_term'] ?? null);
        $this->assertSame('https://referrer.com', $serialized['referrer_url'] ?? null);
        $this->assertSame('https://landing.com', $serialized['landing_page_url'] ?? null);
    }
}
