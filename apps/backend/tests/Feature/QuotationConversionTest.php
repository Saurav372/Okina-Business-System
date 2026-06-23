<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\ProductSku;
use App\Models\Quotation;
use App\Models\QuotationApprovalEvent;
use App\Models\QuotationItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationConversionTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------ helpers

    protected function createAuthorizedUser(): User
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

        $role->permissions()->sync(
            Permission::query()->whereIn('slug', ['quotations.manage'])->pluck('id')->all()
        );

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

    /**
     * Create an approved quotation with at least one real SKU-backed item
     * and a linked customer, ready for conversion.
     */
    protected function createConvertibleQuotation(): Quotation
    {
        $customer = Customer::factory()->create();
        $sku = ProductSku::factory()->create();

        $quotation = Quotation::factory()->create([
            'status' => Quotation::STATUS_APPROVED,
            'customer_id' => $customer->id,
            'subtotal_amount_minor' => 5000,
            'discount_amount_minor' => 500,
            'shipping_amount_minor' => 200,
            'tax_amount_minor' => 225,
            'total_amount_minor' => 4925,
            'currency' => 'INR',
            'customer_snapshot' => [
                'contact_name' => $customer->display_name ?? $customer->name,
                'email' => $customer->email,
            ],
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'product_sku_id' => $sku->id,
            'product_id_snapshot' => $sku->product_id,
            'product_name_snapshot' => $sku->product?->name ?? 'Test Product',
            'sku_code_snapshot' => $sku->sku_code,
            'item_name' => 'Test Product',
            'quantity' => 10,
            'unit_price_minor' => 500,
            'discount_amount_minor' => 0,
            'tax_amount_minor' => 0,
            'line_subtotal_minor' => 5000,
            'line_total_minor' => 5000,
            'currency' => 'INR',
            'sort_order' => 0,
        ]);

        return $quotation;
    }

    // ------------------------------------------------------------------ tests

    public function test_approved_quotation_with_sku_items_converts_to_sales_order(): void
    {
        $user = $this->createAuthorizedUser();
        $quotation = $this->createConvertibleQuotation();

        $response = $this->actingAs($user)->postJson(
            route('admin.quotations.convert', $quotation->public_id),
            ['note' => 'Converting to sales order.']
        );

        $response->assertStatus(201);
        $response->assertJsonPath('quotation.status', 'converted');
        $response->assertJsonStructure([
            'order' => ['public_id', 'status'],
            'quotation' => ['public_id', 'status', 'converted_at'],
        ]);

        $quotation->refresh();
        $this->assertSame('converted', $quotation->status);
        $this->assertNotNull($quotation->converted_order_id);
        $this->assertNotNull($quotation->converted_at);

        // An Order must have been created
        $order = Order::find($quotation->converted_order_id);
        $this->assertNotNull($order);
        $this->assertSame('sales_order', $order->order_type);
        $this->assertSame('quotation_conversion', $order->order_source);
        $this->assertSame('confirmed', $order->status);

        // Items must have been promoted
        $this->assertSame(1, $order->items()->count());
    }

    public function test_converted_order_totals_match_quotation_totals(): void
    {
        $user = $this->createAuthorizedUser();
        $quotation = $this->createConvertibleQuotation();

        $this->actingAs($user)->postJson(
            route('admin.quotations.convert', $quotation->public_id)
        )->assertStatus(201);

        $quotation->refresh();
        $order = Order::find($quotation->converted_order_id);

        $this->assertSame($quotation->subtotal_amount_minor, $order->subtotal_amount_minor);
        $this->assertSame($quotation->discount_amount_minor, $order->discount_amount_minor);
        $this->assertSame($quotation->shipping_amount_minor, $order->shipping_amount_minor);
        $this->assertSame($quotation->tax_amount_minor, $order->tax_amount_minor);
        $this->assertSame($quotation->total_amount_minor, $order->total_amount_minor);
        $this->assertSame($quotation->currency, $order->currency);
    }

    public function test_non_approved_quotation_cannot_be_converted(): void
    {
        $user = $this->createAuthorizedUser();

        foreach (['draft', 'sent', 'revised', 'rejected', 'revision_requested'] as $status) {
            $quotation = Quotation::factory()->create(['status' => $status]);

            $response = $this->actingAs($user)->postJson(
                route('admin.quotations.convert', $quotation->public_id)
            );

            $response->assertStatus(422);
            $response->assertJsonPath('errors.quotation.0', 'Quotation must be in approved status to convert.');
        }
    }

    public function test_already_converted_quotation_cannot_be_converted_again(): void
    {
        $user = $this->createAuthorizedUser();
        $quotation = $this->createConvertibleQuotation();

        // First conversion
        $this->actingAs($user)->postJson(
            route('admin.quotations.convert', $quotation->public_id)
        )->assertStatus(201);

        $quotation->refresh();

        // The quotation is now 'converted' — direct second attempt should fail
        $response = $this->actingAs($user)->postJson(
            route('admin.quotations.convert', $quotation->public_id)
        );

        $response->assertStatus(422);
        $response->assertJsonPath('errors.quotation.0', 'Quotation must be in approved status to convert.');
    }

    public function test_quotation_without_customer_cannot_be_converted(): void
    {
        $user = $this->createAuthorizedUser();
        $sku = ProductSku::factory()->create();

        // Quotation with no customer_id
        $quotation = Quotation::factory()->create([
            'status' => Quotation::STATUS_APPROVED,
            'customer_id' => null,
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'product_sku_id' => $sku->id,
            'item_name' => 'SKU item',
        ]);

        $response = $this->actingAs($user)->postJson(
            route('admin.quotations.convert', $quotation->public_id)
        );

        $response->assertStatus(422);
        $response->assertJsonPath('errors.quotation.0', 'Quotation must be linked to a customer before conversion.');
    }

    public function test_quotation_with_free_text_items_is_blocked(): void
    {
        $user = $this->createAuthorizedUser();
        $customer = Customer::factory()->create();

        $quotation = Quotation::factory()->create([
            'status' => Quotation::STATUS_APPROVED,
            'customer_id' => $customer->id,
        ]);

        // Free-text item: no product_sku_id
        QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'product_sku_id' => null,
            'item_name' => 'Design Consultation',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(
            route('admin.quotations.convert', $quotation->public_id)
        );

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Quotation contains items that cannot be converted to a sales order. Link each item to a valid product/SKU before converting.');
        $response->assertJsonStructure(['unconvertible_items']);

        $unconvertible = $response->json('unconvertible_items');
        $this->assertCount(1, $unconvertible);
        $this->assertSame('Design Consultation', $unconvertible[0]['item_name']);

        // No order should have been created
        $this->assertSame(0, Order::count());
    }

    public function test_conversion_is_idempotent_with_same_key(): void
    {
        $user = $this->createAuthorizedUser();
        $quotation = $this->createConvertibleQuotation();
        $idempotencyKey = 'idem-key-abc-123';

        // First conversion
        $response1 = $this->actingAs($user)->postJson(
            route('admin.quotations.convert', $quotation->public_id),
            ['idempotency_key' => $idempotencyKey]
        );
        $response1->assertStatus(201);
        $firstOrderId = $response1->json('order.public_id');

        // Force quotation status back to approved to simulate a second attempt
        // (idempotency is checked by matching the key, not re-evaluating status)
        // The quotation will be 'converted' at this point; idempotency check comes before status check.
        $quotation->refresh();
        $this->assertSame($idempotencyKey, $quotation->conversion_idempotency_key);

        // Second request with same key
        $response2 = $this->actingAs($user)->postJson(
            route('admin.quotations.convert', $quotation->public_id),
            ['idempotency_key' => $idempotencyKey]
        );
        $response2->assertStatus(200);
        $response2->assertJsonPath('message', 'Quotation conversion processed (idempotent).');
        $response2->assertJsonPath('order.public_id', $firstOrderId);

        // Only one order must exist
        $this->assertSame(1, Order::count());
    }

    public function test_conversion_logs_approval_event(): void
    {
        $user = $this->createAuthorizedUser();
        $quotation = $this->createConvertibleQuotation();

        $this->actingAs($user)->postJson(
            route('admin.quotations.convert', $quotation->public_id),
            ['note' => 'Order ready.']
        )->assertStatus(201);

        $event = QuotationApprovalEvent::query()
            ->where('quotation_id', $quotation->id)
            ->where('event_type', 'converted')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame('staff', $event->actor_type);
        $this->assertSame($user->id, $event->actor_user_id);
        $this->assertSame('Order ready.', $event->note);
    }

    public function test_unauthorized_user_cannot_convert(): void
    {
        // A user with dashboard access but without quotations.manage should be denied.
        $viewOnlyRole = Role::query()->updateOrCreate(
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
        $user->assignRole($viewOnlyRole); // Has dashboard access but no quotations.manage

        $quotation = $this->createConvertibleQuotation();

        $response = $this->actingAs($user)->postJson(
            route('admin.quotations.convert', $quotation->public_id)
        );

        $response->assertStatus(403);
    }

    public function test_order_items_have_correct_price_source(): void
    {
        $user = $this->createAuthorizedUser();
        $quotation = $this->createConvertibleQuotation();

        $this->actingAs($user)->postJson(
            route('admin.quotations.convert', $quotation->public_id)
        )->assertStatus(201);

        $quotation->refresh();
        $order = Order::find($quotation->converted_order_id);

        $order->items->each(function (OrderItem $item): void {
            $this->assertSame('quotation_conversion', $item->price_source);
        });
    }

    public function test_conversion_falls_back_to_quotation_customer_id(): void
    {
        $user = $this->createAuthorizedUser();
        // Customer is set directly on the quotation — no additional input needed.
        $quotation = $this->createConvertibleQuotation();

        $response = $this->actingAs($user)->postJson(
            route('admin.quotations.convert', $quotation->public_id)
        );

        $response->assertStatus(201);

        $quotation->refresh();
        $order = Order::find($quotation->converted_order_id);
        $this->assertNotNull($order->customer_id);
    }

    public function test_converted_quotation_is_terminal(): void
    {
        $user = $this->createAuthorizedUser();
        $quotation = $this->createConvertibleQuotation();

        $this->actingAs($user)->postJson(
            route('admin.quotations.convert', $quotation->public_id)
        )->assertStatus(201);

        $quotation->refresh();

        // A converted quotation cannot transition to any other status.
        $this->assertFalse($quotation->canTransitionTo(Quotation::STATUS_SENT));
        $this->assertFalse($quotation->canTransitionTo(Quotation::STATUS_CANCELLED));
        $this->assertFalse($quotation->canTransitionTo(Quotation::STATUS_APPROVED));
    }
}
