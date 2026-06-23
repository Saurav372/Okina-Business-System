<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function createAuthorizedStaffUser(): User
    {
        Permission::query()->updateOrCreate(
            ['slug' => 'quotations.manage'],
            [
                'name' => 'Manage Quotations',
                'group' => 'quotations',
                'guard_name' => 'web',
                'description' => 'Manage quotations',
                'is_sensitive' => false,
            ]
        );

        $role = Role::query()->updateOrCreate(
            ['slug' => 'quotation_manager'],
            [
                'name' => 'Quotation Manager',
                'guard_name' => 'web',
                'description' => 'Can manage quotations',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );

        $permissionIds = Permission::query()->whereIn('slug', ['quotations.manage'])->pluck('id')->all();
        $role->permissions()->sync($permissionIds);

        $dashboardRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->assignRole($dashboardRole);

        return $user;
    }

    public function test_unauthenticated_guest_is_blocked(): void
    {
        $response = $this->postJson(route('admin.quotations.store'), []);
        $response->assertStatus(401);
    }

    public function test_unauthorized_staff_is_blocked(): void
    {
        $dashboardRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );

        $user = User::factory()->create();
        $user->assignRole($dashboardRole);

        $payload = [
            'contact_name' => 'Unauthorized Staff',
            'email' => 'staff@example.com',
            'quotation_type' => 'bulk_quotation',
            'items' => [
                [
                    'item_name' => 'Custom Mug',
                    'quantity' => 10,
                    'unit_price_minor' => 500,
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload);
        $response->assertStatus(403);
    }

    public function test_authorized_staff_can_create_quotation_from_qualified_lead(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lead = Lead::factory()->create([
            'status' => 'qualified',
            'contact_name' => 'Saurav Sen',
            'email' => 'saurav@example.com',
            'phone' => '+919999999999',
            'company_name' => 'Okina Craft Inc.',
        ]);

        $product = Product::factory()->create();
        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'price_minor' => 1200,
        ]);

        $payload = [
            'lead_public_id' => $lead->public_id,
            'quotation_type' => 'bulk_quotation',
            'items' => [
                [
                    'sku_code' => $sku->sku_code,
                    'item_name' => 'Custom T-Shirt',
                    'quantity' => 10,
                    'unit_price_minor' => 1000,
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'public_id',
            'quotation_number',
            'status',
            'quotation_type',
            'totals' => [
                'subtotal_amount_minor',
                'discount_amount_minor',
                'shipping_amount_minor',
                'tax_amount_minor',
                'total_amount_minor',
                'currency',
            ],
            'valid_until',
            'customer_snapshot' => [
                'contact_name',
                'email',
                'phone',
                'company_name',
            ],
            'items',
            'created_at',
            'updated_at',
        ]);

        // Hide internal DB IDs
        $response->assertJsonMissing(['id', 'lead_id', 'customer_id', 'created_by_user_id']);

        // Assert lead status remains qualified
        $lead->refresh();
        $this->assertSame('qualified', $lead->status);

        // Assert database record exists and snapshot is correct
        $quotation = Quotation::query()->first();
        $this->assertNotNull($quotation);
        $this->assertSame($lead->id, $quotation->lead_id);
        $this->assertSame('Saurav Sen', $quotation->customer_snapshot['contact_name']);
        $this->assertSame('saurav@example.com', $quotation->customer_snapshot['email']);
    }

    public function test_authorized_staff_can_create_quotation_from_customer(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $customer = Customer::factory()->create([
            'display_name' => 'Saurav Customer',
            'email' => 'customer@example.com',
            'phone' => '+918888888888',
            'company_name' => 'Customer Inc.',
        ]);

        $product = Product::factory()->create();
        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'price_minor' => 500,
        ]);

        $payload = [
            'customer_public_id' => $customer->public_id,
            'quotation_type' => 'manual_quotation',
            'items' => [
                [
                    'sku_code' => $sku->sku_code,
                    'item_name' => 'Standard Mug',
                    'quantity' => 5,
                    'unit_price_minor' => 450,
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload);

        $response->assertStatus(201);

        $quotation = Quotation::query()->first();
        $this->assertNotNull($quotation);
        $this->assertSame($customer->id, $quotation->customer_id);
        $this->assertSame('Saurav Customer', $quotation->customer_snapshot['contact_name']);
        $this->assertSame($customer->public_id, $quotation->customer_snapshot['customer_public_id']);
    }

    public function test_authorized_staff_can_create_manual_quotation(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $product = Product::factory()->create();
        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'price_minor' => 200,
        ]);

        $payload = [
            'contact_name' => 'Manual Person',
            'email' => 'manual@example.com',
            'phone' => '9999999999',
            'quotation_type' => 'manual_quotation',
            'items' => [
                [
                    'sku_code' => $sku->sku_code,
                    'item_name' => 'Sticker Pack',
                    'quantity' => 100,
                    'unit_price_minor' => 180,
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload);

        $response->assertStatus(201);

        $quotation = Quotation::query()->first();
        $this->assertNotNull($quotation);
        $this->assertNull($quotation->lead_id);
        $this->assertNull($quotation->customer_id);
        $this->assertSame('Manual Person', $quotation->customer_snapshot['contact_name']);
        $this->assertSame('manual@example.com', $quotation->customer_snapshot['email']);
    }

    public function test_cannot_supply_lead_and_customer_source_simultaneously(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lead = Lead::factory()->create(['status' => 'qualified']);
        $customer = Customer::factory()->create();

        $payload = [
            'lead_public_id' => $lead->public_id,
            'customer_public_id' => $customer->public_id,
            'quotation_type' => 'bulk_quotation',
            'items' => [
                ['item_name' => 'Item', 'quantity' => 1, 'unit_price_minor' => 100],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['source']);
    }

    public function test_cannot_create_empty_source_quotation(): void
    {
        $user = $this->createAuthorizedStaffUser();

        $payload = [
            'quotation_type' => 'bulk_quotation',
            'items' => [
                ['item_name' => 'Item', 'quantity' => 1, 'unit_price_minor' => 100],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['source']);
    }

    public function test_manual_quotation_requires_contact_method(): void
    {
        $user = $this->createAuthorizedStaffUser();

        $payload = [
            'contact_name' => 'Manual Person',
            'quotation_type' => 'manual_quotation',
            'items' => [
                ['item_name' => 'Item', 'quantity' => 1, 'unit_price_minor' => 100],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['contact_name']);
    }

    public function test_lead_source_requires_existing_lead(): void
    {
        $user = $this->createAuthorizedStaffUser();

        $payload = [
            'lead_public_id' => 'LD-NONEXISTENT',
            'quotation_type' => 'bulk_quotation',
            'items' => [
                ['item_name' => 'Item', 'quantity' => 1, 'unit_price_minor' => 100],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['lead_public_id']);
    }

    public function test_customer_source_requires_existing_customer(): void
    {
        $user = $this->createAuthorizedStaffUser();

        $payload = [
            'customer_public_id' => 'CUS-NONEXISTENT',
            'quotation_type' => 'bulk_quotation',
            'items' => [
                ['item_name' => 'Item', 'quantity' => 1, 'unit_price_minor' => 100],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_public_id']);
    }

    public function test_cannot_create_quotation_from_unqualified_lead(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lead = Lead::factory()->create(['status' => 'new']);

        $payload = [
            'lead_public_id' => $lead->public_id,
            'quotation_type' => 'bulk_quotation',
            'items' => [
                ['item_name' => 'Item', 'quantity' => 1, 'unit_price_minor' => 100],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['lead_public_id']);
    }

    public function test_can_create_multiple_quotations_for_same_qualified_lead(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $lead = Lead::factory()->create(['status' => 'qualified']);

        $payload = [
            'lead_public_id' => $lead->public_id,
            'quotation_type' => 'bulk_quotation',
            'items' => [
                ['item_name' => 'Item', 'quantity' => 1, 'unit_price_minor' => 100],
            ],
        ];

        $response1 = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload);
        $response1->assertStatus(201);

        $response2 = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload);
        $response2->assertStatus(201);

        $this->assertSame(2, Quotation::where('lead_id', $lead->id)->count());
    }

    public function test_totals_and_tax_calculation(): void
    {
        $user = $this->createAuthorizedStaffUser();

        $payload = [
            'contact_name' => 'Totals Person',
            'email' => 'totals@example.com',
            'quotation_type' => 'bulk_quotation',
            'discount_amount_minor' => 200,
            'shipping_amount_minor' => 50,
            'tax_rate_percent' => 18,
            'items' => [
                [
                    'item_name' => 'Item A',
                    'quantity' => 2,
                    'unit_price_minor' => 500,
                ],
                [
                    'item_name' => 'Item B',
                    'quantity' => 1,
                    'unit_price_minor' => 300,
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload);

        $response->assertStatus(201);

        // Subtotal = 2 * 500 + 1 * 300 = 1300
        // Discount = 200
        // Tax base = 1300 - 200 = 1100
        // Tax = 1100 * 0.18 = 198
        // Total = 1300 - 200 + 50 + 198 = 1348
        $response->assertJsonPath('totals.subtotal_amount_minor', 1300);
        $response->assertJsonPath('totals.discount_amount_minor', 200);
        $response->assertJsonPath('totals.shipping_amount_minor', 50);
        $response->assertJsonPath('totals.tax_amount_minor', 198);
        $response->assertJsonPath('totals.total_amount_minor', 1348);
    }

    public function test_pricing_fallback_rules(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $product = Product::factory()->create(['base_price_minor' => 300]);
        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'price_minor' => 250,
        ]);

        // 1. Explicit override unit price is used
        $payload1 = [
            'contact_name' => 'Person',
            'email' => 'p@example.com',
            'quotation_type' => 'manual_quotation',
            'items' => [
                [
                    'sku_code' => $sku->sku_code,
                    'item_name' => 'Item',
                    'quantity' => 1,
                    'unit_price_minor' => 500,
                ],
            ],
        ];
        $response1 = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload1);
        $response1->assertStatus(201);
        $response1->assertJsonPath('items.0.unit_price_minor', 500);

        // 2. Fallback to SKU price when request unit_price_minor is null/omitted
        $payload2 = [
            'contact_name' => 'Person',
            'email' => 'p@example.com',
            'quotation_type' => 'manual_quotation',
            'items' => [
                [
                    'sku_code' => $sku->sku_code,
                    'item_name' => 'Item',
                    'quantity' => 1,
                ],
            ],
        ];
        $response2 = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload2);
        $response2->assertStatus(201);
        $response2->assertJsonPath('items.0.unit_price_minor', 250);

        // 3. Fallback to Product base price when SKU price is null
        $skuNoPrice = ProductSku::factory()->create([
            'product_id' => $product->id,
            'price_minor' => null,
        ]);
        $payload3 = [
            'contact_name' => 'Person',
            'email' => 'p@example.com',
            'quotation_type' => 'manual_quotation',
            'items' => [
                [
                    'sku_code' => $skuNoPrice->sku_code,
                    'item_name' => 'Item',
                    'quantity' => 1,
                ],
            ],
        ];
        $response3 = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload3);
        $response3->assertStatus(201);
        $response3->assertJsonPath('items.0.unit_price_minor', 300);

        // 4. Fail validation when neither exists
        $productNoPrice = Product::factory()->create(['base_price_minor' => null]);
        $skuNoPrice2 = ProductSku::factory()->create([
            'product_id' => $productNoPrice->id,
            'price_minor' => null,
        ]);
        $payload4 = [
            'contact_name' => 'Person',
            'email' => 'p@example.com',
            'quotation_type' => 'manual_quotation',
            'items' => [
                [
                    'sku_code' => $skuNoPrice2->sku_code,
                    'item_name' => 'Item',
                    'quantity' => 1,
                ],
            ],
        ];
        $response4 = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload4);
        $response4->assertStatus(422);
        $response4->assertJsonValidationErrors(['items.0.unit_price_minor']);
    }

    public function test_valid_until_defaults_to_30_days_when_omitted_or_null(): void
    {
        $user = $this->createAuthorizedStaffUser();

        // 1. Omitted
        $payloadOmitted = [
            'contact_name' => 'Omitted Person',
            'email' => 'omitted@example.com',
            'quotation_type' => 'manual_quotation',
            'items' => [
                [
                    'item_name' => 'Item A',
                    'quantity' => 1,
                    'unit_price_minor' => 100,
                ],
            ],
        ];

        $response1 = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payloadOmitted);
        $response1->assertStatus(201);
        $response1->assertJsonPath('valid_until', now()->addDays(30)->toDateString());

        // 2. Explicitly null
        $payloadNull = [
            'contact_name' => 'Null Person',
            'email' => 'null@example.com',
            'quotation_type' => 'manual_quotation',
            'valid_until' => null,
            'items' => [
                [
                    'item_name' => 'Item A',
                    'quantity' => 1,
                    'unit_price_minor' => 100,
                ],
            ],
        ];

        $response2 = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payloadNull);
        $response2->assertStatus(201);
        $response2->assertJsonPath('valid_until', now()->addDays(30)->toDateString());
    }

    public function test_tax_calculation_defensive_base_rounding(): void
    {
        $user = $this->createAuthorizedStaffUser();

        // Subtotal = 1000
        // Discount = 1200 (capped at subtotal of 1000 in controller logic)
        // Tax base should be max(0, 1000 - 1000) = 0
        // Tax rate = 18% -> Tax = 0
        $payload = [
            'contact_name' => 'Defensive Person',
            'email' => 'defensive@example.com',
            'quotation_type' => 'manual_quotation',
            'discount_amount_minor' => 1200,
            'tax_rate_percent' => 18,
            'items' => [
                [
                    'item_name' => 'Item A',
                    'quantity' => 1,
                    'unit_price_minor' => 1000,
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('totals.subtotal_amount_minor', 1000);
        $response->assertJsonPath('totals.discount_amount_minor', 1000);
        $response->assertJsonPath('totals.tax_amount_minor', 0);
        $response->assertJsonPath('totals.total_amount_minor', 0);

        // Test PHP_ROUND_HALF_UP on tax:
        // Subtotal = 105
        // Discount = 0
        // Tax base = 105
        // Tax rate = 5% -> 105 * 0.05 = 5.25. PHP_ROUND_HALF_UP with 0 decimals:
        // 5.25 is rounded to 5.
        // Wait, what about 5.5? Let's check 110 * 0.05 = 5.5.
        // 110 * 5% = 5.5 -> PHP_ROUND_HALF_UP -> 6.
        // Let's test both!
        $payloadRound1 = [
            'contact_name' => 'Rounding Person 1',
            'email' => 'round1@example.com',
            'quotation_type' => 'manual_quotation',
            'tax_rate_percent' => 5,
            'items' => [
                [
                    'item_name' => 'Item A',
                    'quantity' => 1,
                    'unit_price_minor' => 110,
                ],
            ],
        ];

        $responseRound1 = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payloadRound1);
        $responseRound1->assertStatus(201);
        $responseRound1->assertJsonPath('totals.tax_amount_minor', 6); // 110 * 0.05 = 5.5 -> 6

        $payloadRound2 = [
            'contact_name' => 'Rounding Person 2',
            'email' => 'round2@example.com',
            'quotation_type' => 'manual_quotation',
            'tax_rate_percent' => 5,
            'items' => [
                [
                    'item_name' => 'Item A',
                    'quantity' => 1,
                    'unit_price_minor' => 109,
                ],
            ],
        ];

        $responseRound2 = $this->actingAs($user)->postJson(route('admin.quotations.store'), $payloadRound2);
        $responseRound2->assertStatus(201);
        $responseRound2->assertJsonPath('totals.tax_amount_minor', 5); // 109 * 0.05 = 5.45 -> 5
    }
}
