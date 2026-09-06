<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_can_be_stored_and_retrieved_consistently(): void
    {
        $service = app(SettingsService::class);

        $service->set('business', 'company_name', 'Okina Craft');
        $service->set('payment', 'online_payments_enabled', false);
        $service->set('upload', 'max_file_size_mb', 12);

        $this->assertSame('Okina Craft', $service->get('business', 'company_name'));
        $this->assertFalse($service->get('payment', 'online_payments_enabled'));
        $this->assertSame(12, $service->get('upload', 'max_file_size_mb'));
        $this->assertSame('INR', $service->get('business', 'default_currency'));
    }

    public function test_settings_are_grouped_into_the_expected_shared_categories(): void
    {
        $service = app(SettingsService::class);

        $groups = $service->groups();

        $this->assertSame([
            'business',
            'payment',
            'notification',
            'seo',
            'upload',
            'integration',
            'documents',
            'tax',
            'payments',
        ], array_keys($groups));

        $this->assertSame('Business', $groups['business']['label']);
        $this->assertSame('Payment', $groups['payment']['label']);
        $this->assertContains('company_name', $groups['business']['keys']);
        $this->assertContains('default_gateway', $groups['payment']['keys']);
        $this->assertContains('google_sheets_enabled', $groups['integration']['keys']);
    }

    public function test_default_settings_are_seeded_and_existing_values_are_not_overwritten(): void
    {
        $service = app(SettingsService::class);

        $service->seedDefaults();

        $this->assertDatabaseCount('settings', 56);
        $this->assertSame('cashfree', $service->get('payment', 'default_gateway'));
        $this->assertSame(5, $service->get('upload', 'max_file_size_mb'));
        $this->assertTrue($service->get('seo', 'robots_index'));

        $service->set('payment', 'default_gateway', 'manual');
        $service->seedDefaults();

        $this->assertSame('manual', $service->get('payment', 'default_gateway'));
        $this->assertSame(1, Setting::query()->forKey('payment', 'default_gateway')->count());
    }

    public function test_persisted_settings_are_available_through_group_access(): void
    {
        $service = app(SettingsService::class);

        $service->set('notification', 'payment_emails_enabled', false);
        $service->set('seo', 'meta_description', 'Custom store description');

        $groupValues = $service->all('notification');

        $this->assertFalse($groupValues['payment_emails_enabled']);
        $this->assertTrue($groupValues['email_enabled']);
        $this->assertSame('Custom store description', $service->get('seo', 'meta_description'));
    }
}
