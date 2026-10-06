<?php

namespace Tests\Feature;

use App\Models\LandingLead;
use App\Models\LandingPage;
use App\Models\MarketingEvent;
use App\Models\MarketingTrackingSetting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class MarketingDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $adsManager;
    private User $unauthorizedStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'admin@okinacraft.com',
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->superAdmin->assignRole(Role::SUPER_ADMIN);

        $this->adsManager = User::factory()->create([
            'email' => 'marketing@okinacraft.com',
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->adsManager->assignRole(Role::ADS_MANAGER);

        $this->unauthorizedStaff = User::factory()->create([
            'email' => 'production@okinacraft.com',
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unauthorizedStaff->assignRole(Role::PRODUCTION_STAFF);
    }

    public function test_ads_manager_can_access_marketing_dashboard(): void
    {
        $this->actingAs($this->adsManager);

        $this->get('/admin/marketing/landing-pages')->assertOk();
        $this->get('/admin/marketing/leads')->assertOk();
        $this->get('/admin/marketing/tracking')->assertOk();
        $this->get('/admin/marketing/analytics')->assertOk();
    }

    public function test_unauthorized_staff_is_denied_access_to_marketing(): void
    {
        $this->actingAs($this->unauthorizedStaff);

        $this->get('/admin/marketing/landing-pages')->assertForbidden();
        $this->get('/admin/marketing/leads')->assertForbidden();
        $this->get('/admin/marketing/tracking')->assertForbidden();
        $this->get('/admin/marketing/analytics')->assertForbidden();
    }

    public function test_ads_manager_is_strictly_denied_access_to_finance_inventory_vendors_settings(): void
    {
        $this->actingAs($this->adsManager);

        // Cannot view or manage users & roles
        $this->get('/admin/staff')->assertForbidden();
        $this->get('/admin/roles')->assertForbidden();

        // Cannot view or manage expenses / finance
        $this->get('/admin/expenses')->assertForbidden();
        $this->get('/admin/payments')->assertForbidden();
        $this->get('/admin/refunds')->assertForbidden();
        $this->get('/admin/reports/finance')->assertForbidden();

        // Cannot view or manage vendors & purchases
        $this->get('/admin/vendors')->assertForbidden();
        $this->get('/admin/purchases')->assertForbidden();

        // Cannot adjust inventory
        $this->get('/admin/inventory')->assertForbidden();

        // Cannot manage system settings or audit
        $this->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_can_edit_landing_page_and_publish_unpublish(): void
    {
        $this->actingAs($this->adsManager);

        $page = LandingPage::firstOrCreate(
            ['slug' => 'custom-t-shirts'],
            LandingPage::defaultContentFor('custom-t-shirts')
        );

        $response = $this->put("/admin/marketing/landing-pages/{$page->id}", [
            'title' => 'Updated B2B Custom T-Shirts',
            'hero' => [
                'badge' => 'New B2B Pricing',
                'title' => 'Best Custom T-Shirts in India',
                'subtitle' => 'Premium 220 GSM bio-washed apparel.',
            ],
            'cta_settings' => [
                'primary_text' => 'Get Instant Quote Now',
                'whatsapp_text' => 'WhatsApp Okina Craft',
                'whatsapp_phone' => '919876543210',
                'whatsapp_message' => 'Custom inquiry message.',
            ],
        ]);

        $response->assertSessionHas('status');
        $page->refresh();

        $this->assertSame('Updated B2B Custom T-Shirts', $page->title);
        $this->assertSame('New B2B Pricing', $page->hero['badge']);
        $this->assertSame('Get Instant Quote Now', $page->cta_settings['primary_text']);

        // Unpublish
        $this->post("/admin/marketing/landing-pages/{$page->id}/unpublish")->assertRedirect();
        $page->refresh();
        $this->assertSame('draft', $page->status);

        // Publish
        $this->post("/admin/marketing/landing-pages/{$page->id}/publish")->assertRedirect();
        $page->refresh();
        $this->assertSame('published', $page->status);
    }

    public function test_can_view_leads_update_status_and_export_csv(): void
    {
        $this->actingAs($this->adsManager);

        $lead = LandingLead::create([
            'name' => 'Rajesh Sharma',
            'phone' => '9876543210',
            'product_type' => 'Company Uniforms',
            'quantity_range' => '50-100',
            'status' => 'new',
            'source' => 'meta',
            'utm_source' => 'meta_ad',
        ]);

        $this->get('/admin/marketing/leads')->assertOk()->assertSee('Rajesh Sharma');
        $this->get("/admin/marketing/leads/{$lead->id}")->assertOk()->assertSee('Rajesh Sharma');

        // Update status to quoted
        $this->patch("/admin/marketing/leads/{$lead->id}/status", [
            'status' => 'quoted',
        ])->assertSessionHas('status');

        $lead->refresh();
        $this->assertSame('quoted', $lead->status);

        // Update status to converted
        $this->patch("/admin/marketing/leads/{$lead->id}/status", [
            'status' => 'converted',
        ])->assertSessionHas('status');

        $lead->refresh();
        $this->assertSame('converted', $lead->status);

        // Export CSV
        $export = $this->get('/admin/marketing/leads/export');
        $export->assertOk();
        $export->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_meta_capi_token_is_masked_for_ads_manager_and_protected(): void
    {
        // Store encrypted token
        $secret = 'EAABTestSecretToken123456789xyz';
        MarketingTrackingSetting::setSetting('meta_capi', [
            'enabled' => true,
            'dataset_id' => '1234567890',
            'access_token' => Crypt::encryptString($secret),
        ]);

        $this->actingAs($this->adsManager);

        $response = $this->get('/admin/marketing/tracking');
        $response->assertOk();

        // Must see masked representation, not full raw token
        $masked = MarketingTrackingSetting::maskToken($secret);
        $response->assertSee($masked);
        $response->assertDontSee($secret);

        // Ads Manager submits update without changing token
        $this->put('/admin/marketing/tracking', [
            'tab' => 'capi',
            'capi_enabled' => '1',
            'dataset_id' => '1234567890',
            'access_token' => $masked, // sends masked string
            'api_version' => 'v19.0',
        ])->assertSessionHas('status');

        // Original secret token remains intact
        $this->assertSame($secret, MarketingTrackingSetting::getMetaCapiToken());
    }

    public function test_api_marketing_events_records_beacons(): void
    {
        $response = $this->postJson('/api/marketing/events', [
            'event_name' => 'WhatsAppClick',
            'landing_page_slug' => 'custom-t-shirts',
            'utm_source' => 'instagram',
            'utm_campaign' => 'diwali_sale',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('marketing_events', [
            'event_name' => 'WhatsAppClick',
            'landing_page_slug' => 'custom-t-shirts',
            'utm_source' => 'instagram',
            'utm_campaign' => 'diwali_sale',
        ]);
    }
}
