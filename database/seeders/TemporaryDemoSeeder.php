<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\ProductSku;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Local demo records use DEMO identifiers and never dispatch external work. */
class TemporaryDemoSeeder extends Seeder
{
    private function row(string $table, array $key, array $values): int
    {
        $existing = DB::table($table)->where($key)->value('id');
        $timestamps = $table === 'audit_logs' ? ['created_at' => now()] : ['created_at' => now(), 'updated_at' => now()];

        return $existing ?? DB::table($table)->insertGetId($key + $values + $timestamps);
    }

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('Temporary data is only available locally.');
        }
        config(['sheets.enabled' => false]);
        DB::transaction(function (): void {
            $this->call([SettingsSeeder::class, ExpenseCategorySeeder::class]);
            if (! ProductSku::exists()) {
                $this->call(StorefrontDemoSeeder::class);
            }
            $actor = User::where('email', 'test@example.com')->firstOrFail()->id;
            $skus = ProductSku::with('product')->get();
            $names = ['Aarav Sharma', 'Priya Mehta', 'Rohan Das', 'Ananya Rao', 'Vikram Patel', 'Neha Singh', 'Kabir Shah', 'Isha Nair'];
            $customers = [];
            foreach ($names as $i => $name) {
                $customer = Customer::firstOrCreate(['email' => 'demo.customer'.($i + 1).'@example.com'], [
                    'name' => $name, 'display_name' => $name, 'company_name' => ['Demo Studio', 'Demo Campus Club', 'Demo Cafe', 'Demo Events'][$i % 4],
                    'customer_type' => 'individual', 'status' => 'active', 'source' => 'manual', 'accepts_marketing' => false,
                ]);
                $address = CustomerAddress::firstOrCreate(['customer_id' => $customer->id, 'label' => 'Demo address'], [
                    'address_type' => 'both', 'contact_name' => $name, 'phone' => '0000000000',
                    'address_line_1' => ($i + 10).' Sample Street', 'city' => 'Bhubaneswar', 'state' => 'Odisha', 'postal_code' => '751001',
                    'country_code' => 'IN', 'is_default_shipping' => true, 'is_default_billing' => true,
                ]);
                $customers[] = [$customer, $address];
            }
            $statuses = ['pending_payment', 'confirmed', 'in_production', 'ready_to_ship', 'shipped', 'delivered', 'cancelled', 'refunded'];
            for ($i = 0; $i < 24; $i++) {
                [$customer, $address] = $customers[$i % 8];
                $sku = $skus[$i % $skus->count()];
                $quantity = 5 + $i;
                $total = $sku->price_minor * $quantity;
                $date = $i < 8 ? now()->subDays($i) : now()->subDays(($i - 7) * 9);
                $status = $statuses[$i % 8];
                $order = $this->row('orders', ['public_id' => 'OD-DEMO-'.sprintf('%03d', $i + 1)], [
                    'order_type' => 'website_order', 'order_source' => 'website', 'status' => $status,
                    'customer_id' => $customer->id, 'shipping_address_id' => $address->id, 'billing_address_id' => $address->id,
                    'customer_snapshot' => json_encode($customer->toArray()), 'shipping_address_snapshot' => json_encode($address->toArray()),
                    'billing_address_snapshot' => json_encode($address->toArray()), 'subtotal_amount_minor' => $total, 'total_amount_minor' => $total,
                    'currency' => 'INR', 'placed_at' => $date, 'created_at' => $date, 'internal_notes' => 'Temporary DEMO order',
                    'design_approved' => ! in_array($status, ['pending_payment', 'cancelled']),
                ]);
                $this->row('order_items', ['public_id' => 'OI-DEMO-'.($i + 1)], [
                    'order_id' => $order, 'product_id' => $sku->product_id, 'sku_id' => $sku->id, 'quantity' => $quantity,
                    'product_name_snapshot' => $sku->product->name, 'product_slug_snapshot' => $sku->product->slug, 'sku_code_snapshot' => $sku->sku_code,
                    'customization_fingerprint' => hash('sha256', 'demo-'.$i), 'customization_snapshot' => json_encode(['notes' => 'Demo team logo, front print']),
                    'unit_price_minor' => $sku->price_minor, 'line_subtotal_minor' => $total, 'line_total_minor' => $total, 'currency' => 'INR', 'price_source' => 'catalog',
                ]);
                if (! in_array($status, ['pending_payment', 'cancelled'])) {
                    $payment = $this->row('payments', ['receipt_number' => 'PAY-DEMO-'.($i + 1)], [
                        'order_id' => $order, 'payment_type' => 'full', 'provider' => 'manual', 'method' => 'bank_transfer', 'status' => 'succeeded',
                        'amount_minor' => $total, 'currency' => 'INR', 'paid_at' => $date, 'created_at' => $date, 'recorded_by_user_id' => $actor, 'notes' => 'Temporary DEMO payment; no money moved.',
                    ]);
                    if ($status === 'refunded') {
                        $this->row('refunds', ['provider_refund_id' => 'REF-DEMO-'.($i + 1)], [
                            'order_id' => $order, 'payment_id' => $payment, 'provider' => 'manual', 'refund_type' => 'full', 'status' => 'succeeded',
                            'amount_minor' => $total, 'currency' => 'INR', 'reason_note' => 'Temporary DEMO cancellation refund',
                            'requested_at' => $date, 'approved_at' => $date, 'processed_at' => $date, 'requested_by_user_id' => $actor, 'approved_by_user_id' => $actor,
                        ]);
                    }
                }
                $this->row('notification_logs', ['dedupe_key' => 'DEMO-order-'.$i], [
                    'event_type' => 'order.created', 'channel' => 'database', 'status' => 'skipped', 'recipient_type' => 'customer',
                    'recipient_customer_id' => $customer->id, 'recipient_address' => $customer->email, 'subject_rendered' => 'DEMO order update',
                    'body_summary' => 'Temporary preview only. No notification sent.', 'related_type' => 'order', 'related_id' => $order,
                ]);
                $this->row('audit_logs', ['event_id' => sprintf('00000000-0000-4000-8000-%012d', $i + 1)], [
                    'action' => 'demo.order_created', 'module' => 'orders', 'summary' => 'Temporary DEMO order '.($i + 1).' created',
                    'actor_type' => 'user', 'actor_user_id' => $actor, 'actor_label_snapshot' => 'Demo administrator',
                    'subject_type' => 'order', 'subject_id' => $order, 'occurred_at' => $date,
                ]);
            }
            foreach ($skus as $i => $sku) {
                if (DB::table('inventory_movements')->where('product_sku_id', $sku->id)->exists()) {
                    continue;
                }
                $stock = $i % 6 === 0 ? 0 : ($i % 5 === 0 ? 4 : 60 + $i);
                $inventory = $this->row('inventory_items', ['product_sku_id' => $sku->id], ['on_hand_quantity' => 0, 'reserved_quantity' => 0, 'available_quantity' => 0]);
                DB::table('inventory_items')->where('id', $inventory)->update(['on_hand_quantity' => $stock, 'reserved_quantity' => 0, 'available_quantity' => $stock, 'low_stock_threshold' => 10, 'last_movement_at' => now()]);
                DB::table('product_skus')->where('id', $sku->id)->update(['track_stock' => true, 'stock_quantity' => $stock]);
                $this->row('inventory_movements', ['idempotency_key' => 'DEMO-opening-'.$sku->id], [
                    'product_sku_id' => $sku->id, 'inventory_item_id' => $inventory, 'movement_type' => 'manual_adjustment', 'direction' => 'in', 'quantity' => $stock,
                    'before_on_hand_quantity' => 0, 'after_on_hand_quantity' => $stock, 'before_reserved_quantity' => 0, 'after_reserved_quantity' => 0,
                    'before_available_quantity' => 0, 'after_available_quantity' => $stock, 'occurred_at' => now(), 'notes' => 'Temporary DEMO opening stock', 'created_by_user_id' => $actor,
                ]);
            }
            foreach (['Demo Cotton Supply', 'Demo Print Materials', 'Demo Packaging Co', 'Demo Apparel Works'] as $i => $name) {
                $sku = $skus[$i % $skus->count()];
                $vendor = $this->row('vendors', ['vendor_code' => 'VEN-DEMO-'.($i + 1)], ['name' => $name, 'status' => 'active', 'email' => 'demo.vendor'.$i.'@example.com', 'city' => 'Bhubaneswar', 'country_code' => 'IN', 'payment_terms' => 'Net 30', 'notes' => 'Temporary DEMO supplier']);
                $purchase = $this->row('vendor_orders', ['public_id' => 'PO-DEMO-'.($i + 1)], [
                    'vendor_id' => $vendor, 'status' => $i === 0 ? 'draft' : 'ordered', 'payment_status' => $i === 0 ? 'unpaid' : 'partially_paid',
                    'ordered_at' => now()->subDays(4), 'expected_at' => now()->addDays($i + 2), 'subtotal_amount_minor' => 500000, 'total_amount_minor' => 500000, 'currency' => 'INR', 'notes' => 'Temporary DEMO purchase',
                ]);
                $this->row('vendor_order_items', ['vendor_order_id' => $purchase, 'product_sku_id' => $sku->id], ['sku_code_snapshot' => $sku->sku_code, 'product_name_snapshot' => $sku->product->name, 'quantity_ordered' => 20, 'quantity_received' => 0, 'unit_cost_minor' => 25000, 'line_total_minor' => 500000]);
                if ($i > 0) {
                    $this->row('vendor_payments', ['reference' => 'VP-DEMO-'.$i], ['vendor_order_id' => $purchase, 'status' => 'paid', 'payment_method' => 'bank_transfer', 'amount_minor' => 200000, 'currency' => 'INR', 'paid_at' => now()->subDays(2), 'notes' => 'Temporary DEMO payment']);
                }
                $this->row('warehouse_transfers', ['transfer_code' => 'TR-DEMO-'.$i], ['product_sku_id' => $sku->id, 'source_location' => 'main_warehouse', 'destination_location' => 'store', 'quantity' => 5, 'status' => 'draft', 'initiated_by_user_id' => $actor, 'notes' => 'Temporary DEMO transfer']);
            }
            $categories = DB::table('expense_categories')->pluck('id');
            for ($i = 0; $i < 18; $i++) {
                $status = ['approved', 'approved', 'pending_approval', 'draft', 'rejected', 'approved'][$i % 6];
                $this->row('expenses', ['reference' => 'EXP-DEMO-'.($i + 1)], [
                    'public_id' => 'EXP-DEMO-'.($i + 1), 'expense_category_id' => $categories[$i % $categories->count()], 'amount_minor' => 45000 + $i * 12500,
                    'currency' => 'INR', 'notes' => 'Temporary DEMO: '.['Courier charges', 'Cotton fabric', 'Campaign artwork', 'Printing ink', 'Office internet', 'Packing supplies'][$i % 6],
                    'recorded_by_user_id' => $actor, 'status' => $status, 'occurred_at' => now()->subDays($i * 2),
                    'approved_at' => $status === 'approved' ? now()->subDays($i * 2) : null, 'approved_by_user_id' => $status === 'approved' ? $actor : null,
                ]);
            }
            $this->row('google_sheets_sync_logs', ['unique_value' => 'DEMO-preview-only'], [
                'model_class' => Customer::class, 'model_id' => $customers[0][0]->id, 'unique_key' => 'demo', 'status' => 'failed', 'attempts' => 0,
                'payload_hash' => hash('sha256', 'demo'), 'payload' => json_encode(['demo' => true]), 'error_message' => 'DEMO preview only: no external sync was attempted.', 'triggered_by' => 'manual',
            ]);
        });
    }
}
