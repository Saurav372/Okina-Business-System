<?php

namespace Tests\Feature;

use App\Enums\InventoryDirection;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryMovementType;
use App\Enums\LeadFollowUpStatus;
use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Support\GoogleSheets\GoogleSheetsPayloadMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GoogleSheetsPayloadMappingTest extends TestCase
{
    use RefreshDatabase;

    private GoogleSheetsPayloadMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->mapper = new GoogleSheetsPayloadMapper;
    }

    /**
     * Test config column key uniqueness and header count/order equality for all supported models.
     */
    public function test_config_uniqueness_and_header_counts(): void
    {
        $entities = config('sheets.entities');

        foreach ($entities as $modelClass => $config) {
            $columns = $config['columns'];

            // Assert configured keys are unique
            $this->assertEquals(
                count(array_keys($columns)),
                count(array_unique(array_keys($columns))),
                "Duplicate column keys found in configuration for [{$modelClass}]."
            );

            // Assert headers() matches array_values of configured columns
            $this->assertEquals(
                array_values($columns),
                $this->mapper->headers($modelClass),
                "headers() mismatch for [{$modelClass}]."
            );

            // Assert sheet name resolves correctly
            $this->assertEquals(
                $config['sheet'],
                $this->mapper->sheet($modelClass)
            );
        }
    }

    /**
     * Test mapping of Lead model verifying field resolution and UTC date normalization.
     *
     * The timezone conversion test uses an in-memory model (not DB-persisted) so
     * that the Carbon object retains its original timezone and UTC conversion can
     * be verified. DB-persisted timestamps lose timezone info when stored as strings.
     */
    public function test_lead_mapping_field_resolution(): void
    {
        $lead = Lead::factory()->create([
            'contact_name' => 'Lead Name',
            'email' => 'lead@example.com',
            'phone' => '1234567890',
            'source' => 'manual',
            'status' => 'new',
        ]);

        $payload = $this->mapper->map($lead);

        $this->assertCount(count($this->mapper->headers(Lead::class)), $payload);
        $this->assertEquals($lead->id, $payload['id']);
        $this->assertEquals('Lead Name', $payload['contact_name']);
        $this->assertEquals('lead@example.com', $payload['email']);
        $this->assertEquals('1234567890', $payload['phone']);
        $this->assertEquals('manual', $payload['source']);
        $this->assertEquals('new', $payload['status']);

        $this->assertFlatPayload($payload);
    }

    /**
     * Test that datetime fields are correctly converted to UTC ISO-8601 format.
     * Uses an in-memory model with setRawAttributes to bypass Eloquent's datetime
     * serialization, which would strip timezone metadata before it reaches the mapper.
     */
    public function test_utc_conversion_on_non_utc_timezone(): void
    {
        // Asia/Kolkata = UTC+5:30, so 10:00:00 IST is 04:30:00 UTC
        $nonUtcCarbon = Carbon::parse('2026-07-01 10:00:00', 'Asia/Kolkata');

        $lead = new Lead;
        // setRawAttributes stores Carbon directly, bypassing Eloquent's fromDateTime() cast
        // which would discard timezone info by serializing to a UTC string.
        $lead->setRawAttributes([
            'id' => 99,
            'contact_name' => 'Test',
            'email' => 'test@example.com',
            'phone' => null,
            'source' => 'manual',
            'status' => 'new',
            'created_at' => $nonUtcCarbon,
        ]);

        $payload = $this->mapper->map($lead);

        // Verify correct UTC instant — not just a Z suffix appended to IST time
        $this->assertEquals('2026-07-01T04:30:00Z', $payload['created_at']);
    }

    /**
     * Test mapping of Order model — specifically the N+1 relation guard.
     */
    public function test_order_mapping_relation_guard(): void
    {
        // Bypass factory's booted() display_name override by using direct DB insert
        $customer = Customer::factory()->create();
        // Update directly via DB to bypass Eloquent observer/cast overrides
        \Illuminate\Support\Facades\DB::table('customers')
            ->where('id', $customer->id)
            ->update(['display_name' => 'John Doe']);
        $customer->refresh(); // Re-read from DB so in-memory matches
        $shippingAddress = CustomerAddress::factory()->create(['customer_id' => $customer->id]);
        $billingAddress = CustomerAddress::factory()->billing()->create(['customer_id' => $customer->id]);

        $order = Order::create([
            'public_id' => 'ORD-123',
            'customer_id' => $customer->id,
            'status' => 'pending_payment',
            'total_amount_minor' => 12550,
            'courier_name' => 'DHL',
            'shipping_address_id' => $shippingAddress->id,
            'billing_address_id' => $billingAddress->id,
            'customer_snapshot' => [],
            'shipping_address_snapshot' => [],
            'billing_address_snapshot' => [],
        ]);

        // Without loaded relation, customer_name returns null (N+1 guard)
        $payloadNoRelation = $this->mapper->map($order);
        $this->assertNull($payloadNoRelation['customer_name']);

        // With loaded relation, customer_name resolves correctly
        $order->load('customer');
        $payload = $this->mapper->map($order);
        $this->assertEquals('John Doe', $payload['customer_name']);
        $this->assertEquals('ORD-123', $payload['public_id']);
        $this->assertEquals(125.5, $payload['total_amount']);
        $this->assertEquals('DHL', $payload['courier_name']);

        $this->assertFlatPayload($payload);
    }

    /**
     * Test mapping of Payment model.
     */
    public function test_payment_mapping(): void
    {
        $order = Order::factory()->create(['public_id' => 'ORD-ABC']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => 'online',
            'provider' => 'stripe',
            'method' => Payment::METHOD_BANK_TRANSFER,
            'amount_minor' => 5000,
            'status' => Payment::STATUS_SUCCEEDED,
        ]);

        // Without loaded order — order_public_id returns null
        $payloadNoOrder = $this->mapper->map($payment);
        $this->assertNull($payloadNoOrder['order_public_id']);

        // With loaded order
        $payment->load('order');
        $payload = $this->mapper->map($payment);
        $this->assertEquals('ORD-ABC', $payload['order_public_id']);
        $this->assertEquals('stripe', $payload['provider']);
        $this->assertEquals(Payment::METHOD_BANK_TRANSFER, $payload['method']);
        $this->assertEquals(50.0, $payload['amount']);
        $this->assertEquals($payment->id, $payload['id']);

        $this->assertFlatPayload($payload);
    }

    /**
     * Test mapping of InventoryMovement model.
     */
    public function test_inventory_movement_mapping(): void
    {
        $sku = ProductSku::factory()->create(['sku_code' => 'SKU-XYZ']);
        // ProductSkuObserver auto-creates an InventoryItem on SKU creation
        $inventoryItem = $sku->inventoryItem;
        $movement = InventoryMovement::create([
            'product_sku_id' => $sku->id,
            'inventory_item_id' => $inventoryItem->id,
            'movement_type' => InventoryMovementType::STOCK_IN,
            'direction' => InventoryDirection::IN,
            'quantity' => 10,
            'before_on_hand_quantity' => 5,
            'after_on_hand_quantity' => 15,
            'reason_code' => InventoryMovementReason::PURCHASE_RECEIPT,
            'occurred_at' => now(),
        ]);

        // Without loaded productSku — sku_code returns null
        $payloadNoSku = $this->mapper->map($movement);
        $this->assertNull($payloadNoSku['sku_code']);
        $this->assertEquals(15, $payloadNoSku['balance_on_hand']);
        $this->assertEquals(10, $payloadNoSku['quantity']);

        // Type and reason resolve from backed enum values
        $this->assertEquals(InventoryMovementType::STOCK_IN->value, $payloadNoSku['type']);
        $this->assertEquals(InventoryMovementReason::PURCHASE_RECEIPT->value, $payloadNoSku['reason']);

        // With loaded productSku
        $movement->load('productSku');
        $payload = $this->mapper->map($movement);
        $this->assertEquals('SKU-XYZ', $payload['sku_code']);

        $this->assertFlatPayload($payload);
    }

    /**
     * Test mapping of Customer model.
     */
    public function test_customer_mapping(): void
    {
        $customer = Customer::factory()->create([
            'public_id' => 'CUST-888',
            'display_name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'phone' => '9876543210',
            'status' => 'active',
        ]);

        $payload = $this->mapper->map($customer);

        $this->assertCount(count($this->mapper->headers(Customer::class)), $payload);
        $this->assertEquals('CUST-888', $payload['public_id']);
        $this->assertEquals('Alice Smith', $payload['display_name']);
        $this->assertEquals('active', $payload['status']);

        $this->assertFlatPayload($payload);
    }

    /**
     * Test mapping of LeadFollowUp model — N+1 guard and UTC conversion.
     */
    public function test_lead_follow_up_mapping(): void
    {
        $lead = Lead::factory()->create();
        $staff = User::factory()->create([
            'name' => 'Agent Cooper',
            'user_type' => User::TYPE_STAFF,
        ]);

        $followUp = LeadFollowUp::create([
            'lead_id' => $lead->id,
            'assigned_to_user_id' => $staff->id,
            'status' => LeadFollowUpStatus::PENDING,
            'due_at' => now(),
        ]);

        // Without loaded assignedTo — assigned_staff returns null
        $payloadNoStaff = $this->mapper->map($followUp);
        $this->assertNull($payloadNoStaff['assigned_staff']);
        $this->assertEquals($lead->id, $payloadNoStaff['lead_id']);

        // With loaded assignedTo
        $followUp->load('assignedTo');
        $payload = $this->mapper->map($followUp);
        $this->assertEquals('Agent Cooper', $payload['assigned_staff']);
        // Status enum value should resolve
        $this->assertEquals(LeadFollowUpStatus::PENDING->value, $payload['status']);

        $this->assertFlatPayload($payload);
    }

    /**
     * Test UTC date conversion using in-memory model to preserve timezone info.
     */
    public function test_utc_conversion_on_follow_up_due_at(): void
    {
        // 2026-07-05 14:00:00 Asia/Kolkata = 2026-07-05 08:30:00 UTC
        $nonUtcDueAt = Carbon::parse('2026-07-05 14:00:00', 'Asia/Kolkata');

        $followUp = new LeadFollowUp;
        $followUp->setRawAttributes([
            'id' => 1,
            'lead_id' => 1,
            'status' => LeadFollowUpStatus::PENDING,
            'due_at' => $nonUtcDueAt,
            'created_at' => Carbon::now('UTC'),
        ]);

        $payload = $this->mapper->map($followUp);
        $this->assertEquals('2026-07-05T08:30:00Z', $payload['due_at']);
    }

    /**
     * Test mapping of VendorOrder model.
     */
    public function test_vendor_order_mapping(): void
    {
        $vendor = Vendor::create([
            'name' => 'Global Inc',
            'vendor_code' => 'VND-GBL',
            'status' => VendorStatus::ACTIVE->value,
        ]);

        $vendorOrder = VendorOrder::create([
            'vendor_id' => $vendor->id,
            'public_id' => 'PO-888',
            'status' => VendorOrderStatus::DRAFT,
            'payment_status' => VendorOrderPaymentStatus::UNPAID,
            'total_amount_minor' => 15000,
        ]);

        // Without loaded vendor — vendor_code returns null
        $payloadNoVendor = $this->mapper->map($vendorOrder);
        $this->assertNull($payloadNoVendor['vendor_code']);
        $this->assertEquals('draft', $payloadNoVendor['status']);
        $this->assertEquals('unpaid', $payloadNoVendor['payment_status']);
        $this->assertEquals(150.0, $payloadNoVendor['total_amount']);

        // With loaded vendor
        $vendorOrder->load('vendor');
        $payload = $this->mapper->map($vendorOrder);
        $this->assertEquals('VND-GBL', $payload['vendor_code']);

        $this->assertFlatPayload($payload);
    }

    /**
     * Test that unsupported models throw InvalidArgumentException.
     */
    public function test_unsupported_model_throws_exception(): void
    {
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->mapper->map($user);
    }

    /**
     * Assert that the payload is strictly flat — only scalars and null allowed.
     */
    private function assertFlatPayload(array $payload): void
    {
        foreach ($payload as $key => $value) {
            $this->assertFalse(
                is_array($value),
                "Value for key [{$key}] must not be an array."
            );
            $this->assertFalse(
                is_object($value),
                "Value for key [{$key}] must not be an object."
            );
            $this->assertTrue(
                is_string($value) || is_numeric($value) || is_bool($value) || is_null($value),
                "Value for key [{$key}] must be a scalar or null (got: ".gettype($value).').'
            );
        }
    }
}
