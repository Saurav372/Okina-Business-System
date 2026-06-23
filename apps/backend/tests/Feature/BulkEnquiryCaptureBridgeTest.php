<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkEnquiryCaptureBridgeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the full bulk enquiry bridge: checkout blocks bulk quantities
     * and directs the customer to submit a bulk enquiry lead.
     */
    public function test_bulk_checkout_leads_to_bulk_enquiry_capture(): void
    {
        // 1. Set up catalog and customer
        $category = ProductCategory::factory()->create(['slug' => 'apparel']);
        $product = Product::factory()->create([
            'slug' => 'custom-shirt',
            'name' => 'Custom Shirt',
            'primary_category_id' => $category->id,
            'base_price_minor' => 1000,
            'customization_mode' => Product::CUSTOMIZATION_REQUIRED,
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
            'published_at' => now(),
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size',
            'code' => 'size',
            'values' => [['code' => 'l', 'label' => 'Large']],
            'is_required' => true,
        ]);
        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-SHIRT-L',
            'variant_key' => 'size:l',
            'status' => 'active',
            'price_minor' => 1200,
        ]);

        $customerAccount = CustomerAccount::factory()->create();
        $customer = $customerAccount->customer;

        // 2. Put 25 items in the customer's cart (triggering bulk threshold)
        $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/items', [
                'product_slug' => $product->slug,
                'sku_code' => $sku->sku_code,
                'quantity' => 25,
                'customization_snapshot' => [
                    'schema_version' => 1,
                    'product' => [
                        'slug' => $product->slug,
                        'name' => $product->name,
                    ],
                    'sku_code' => $sku->sku_code,
                    'variant_key' => $sku->variant_key,
                    'selected_options_snapshot' => [
                        [
                            'option_code' => 'size',
                            'value_code' => 'l',
                            'value_label' => 'Large',
                        ],
                    ],
                    'print_method' => 'dtf',
                    'print_position' => 'front',
                    'placement' => [
                        'x' => 42,
                        'y' => 58,
                        'scale' => 0.72,
                        'rotation' => 0,
                    ],
                    'customer_note' => 'Keep centered',
                ],
            ])
            ->assertOk();

        // 3. Call checkout validation and verify it is blocked
        $response = $this->actingAs($customerAccount, 'customer')
            ->postJson('/api/cart/checkout/validation', []);

        $response->assertOk();
        $response->assertJsonPath('data.valid', false);
        $response->assertJsonPath('data.bulk_handoff.required', true);
        $response->assertJsonPath('data.bulk_handoff.next_step', 'bulk_enquiry');
        $response->assertJsonPath('data.errors.0.code', 'bulk_quantity_threshold_reached');

        // 4. Submit the bulk enquiry to the leads capture API
        $enquiryPayload = [
            'contact_name' => $customer->display_name,
            'email' => $customer->email,
            'phone' => $customer->phone ?? '+919999999999',
            'interest_summary' => 'Enquiry for 25 custom shirts from checkout handoff',
            'requirements' => 'Requires custom print placements',
            'product_interest' => ['Custom Shirt'],
            'utm_source' => 'organic',
            'utm_medium' => 'search',
        ];

        $leadResponse = $this->postJson(route('api.catalog.leads.store'), $enquiryPayload);

        $leadResponse->assertStatus(201);
        $leadResponse->assertJsonStructure([
            'public_id',
            'source',
            'status',
            'contact_name',
            'email',
        ]);

        // 5. Verify the Lead record in the database
        $lead = Lead::query()->first();
        $this->assertNotNull($lead);
        $this->assertSame('website_bulk_enquiry', $lead->source);
        $this->assertSame('new', $lead->status);
        $this->assertSame('organic', $lead->utm_source);
        $this->assertSame('search', $lead->utm_medium);
        $this->assertSame($customer->display_name, $lead->contact_name);
        $this->assertSame($customer->email, $lead->email);
    }
}
