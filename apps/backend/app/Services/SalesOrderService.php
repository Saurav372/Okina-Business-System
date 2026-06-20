<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductSku;
use App\Support\Orders\SalesOrderRules;
use App\Support\Products\CustomizationSnapshotBuilder;
use Illuminate\Support\Facades\DB;

readonly class SalesOrderService
{
    public function __construct(
        private readonly CustomizationSnapshotBuilder $snapshots,
        private readonly SalesOrderRules $rules,
    ) {}

    /**
     * Create a sales order from admin-provided payload.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input, mixed $actor): Order
    {
        $customer = Customer::query()->findOrFail($input['customer_id']);

        $items = $input['items'] ?? [];

        $currency = 'INR';
        $subtotal = 0;

        $orderItemAttributes = [];

        foreach ($items as $item) {
            $sku = ProductSku::query()
                ->where('sku_code', $item['sku_code'])
                ->with('product')
                ->firstOrFail();

            $product = $sku->product;

            $unitPrice = is_int($sku->price_minor) ? max(0, $sku->price_minor) : (int) ($product->base_price_minor ?? 0);
            $quantity = (int) ($item['quantity'] ?? 1);
            $lineTotal = $unitPrice * max(1, $quantity);

            $currency = strtoupper($product->currency ?? $currency) ?: $currency;

            $priceSource = is_int($sku->price_minor) ? 'sku_price' : (is_int($product->base_price_minor) ? 'product_base_price' : 'unpriced');

            $customSnapshot = $this->snapshots->publicCartSnapshot($item['customization_snapshot'] ?? []);
            $fingerprint = $this->snapshots->customizationFingerprint($item['customization_snapshot'] ?? []);

            $orderItemAttributes[] = [
                'product_id' => $product->id,
                'sku_id' => $sku->id,
                'quantity' => $quantity,
                'product_name_snapshot' => $product->name,
                'product_slug_snapshot' => $product->slug,
                'sku_code_snapshot' => $sku->sku_code,
                'customization_fingerprint' => $fingerprint,
                'customization_snapshot' => $customSnapshot,
                'unit_price_minor' => (int) $unitPrice,
                'line_subtotal_minor' => (int) $lineTotal,
                'line_total_minor' => (int) $lineTotal,
                'currency' => $currency,
                'price_source' => $priceSource,
            ];

            $subtotal += $lineTotal;
        }

        $total = $subtotal;

        $order = DB::transaction(function () use ($customer, $orderItemAttributes, $subtotal, $total, $currency, $input, $actor) {
            $order = Order::create([
                'order_type' => $this->rules->orderType(),
                'order_source' => $this->rules->orderSource(),
                'status' => $this->rules->initialStatus(),
                'customer_id' => $customer->id,
                'customer_snapshot' => [
                    'public_id' => $customer->public_id,
                    'name' => $customer->display_name ?? $customer->name,
                    'email' => $customer->email ?? null,
                ],
                'subtotal_amount_minor' => (int) $subtotal,
                'discount_amount_minor' => 0,
                'shipping_amount_minor' => 0,
                'tax_amount_minor' => 0,
                'total_amount_minor' => (int) $total,
                'currency' => $currency,
                'created_by_user_id' => $actor?->id ?? null,
                'internal_notes' => isset($input['advance_payment']) ? json_encode(['payment_schedule' => $input['advance_payment']]) : null,
            ]);

            $orderItems = $order->items()->createMany($orderItemAttributes);

            $order->setRelation('items', $orderItems);

            return $order;
        });

        return $order->fresh();
    }
}
