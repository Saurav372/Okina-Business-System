<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductSku;
use App\Support\Orders\OrderTotalsCalculator;
use App\Support\Orders\SalesOrderRules;
use App\Support\Products\CustomizationSnapshotBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

readonly class SalesOrderService
{
    public function __construct(
        private readonly CustomizationSnapshotBuilder $snapshots,
        private readonly SalesOrderRules $rules,
        private readonly OrderTotalsCalculator $totalsCalculator,
        private readonly SettingsService $settingsService,
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

        $orderItemAttributes = [];
        $lineTotals = [];

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

            $lineTotals[] = (int) $lineTotal;
        }
        $discountAmount = isset($input['discount_amount_minor']) ? (int) $input['discount_amount_minor'] : 0;
        $shippingAmount = isset($input['shipping_amount_minor']) ? (int) $input['shipping_amount_minor'] : 0;
        $enableGst = $this->settingsService->get('tax', 'enable_gst', false);
        $taxAmount = $enableGst ? (isset($input['tax_amount_minor']) ? (int) $input['tax_amount_minor'] : 0) : 0;

        $totals = $this->totalsCalculator->fromLineTotals(
            $lineTotals,
            $discountAmount,
            $shippingAmount,
            $taxAmount,
        );

        $paymentSchedule = isset($input['advance_payment']) ? ['payment_schedule' => $input['advance_payment']] : null;
 
        $order = DB::transaction(function () use ($customer, $orderItemAttributes, $totals, $currency, $input, $actor, $paymentSchedule) {
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
                'subtotal_amount_minor' => $totals->subtotalAmountMinor(),
                'discount_amount_minor' => $totals->discountAmountMinor(),
                'shipping_amount_minor' => $totals->shippingAmountMinor(),
                'tax_amount_minor' => $totals->taxAmountMinor(),
                'total_amount_minor' => $totals->totalAmountMinor(),
                'currency' => $currency,
                'created_by_user_id' => $actor?->id ?? null,
                'internal_notes' => $paymentSchedule ? json_encode($paymentSchedule) : ($input['internal_notes'] ?? null),
                'order_metadata' => $paymentSchedule,
            ]);

            $orderItems = $order->items()->createMany($orderItemAttributes);

            $order->setRelation('items', $orderItems);

            return $order;
        });

        return $order->fresh();
    }

    /**
     * Update an existing sales order.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(Order $order, array $input, mixed $actor): Order
    {
        $fieldsToTrack = [
            'customer_id',
            'status',
            'subtotal_amount_minor',
            'discount_amount_minor',
            'shipping_amount_minor',
            'tax_amount_minor',
            'total_amount_minor',
            'internal_notes',
        ];

        // Fetch original state before updates
        $originalState = $order->only($fieldsToTrack);

        DB::transaction(function () use ($order, $input, $actor, $fieldsToTrack, $originalState) {
            // Lock the order inside transaction
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            // Enforce edit rules
            if (! $order->isEditable()) {
                throw ValidationException::withMessages([
                    'order' => 'This order can no longer be edited.',
                ]);
            }

            // Lock existing items
            $existingItems = $order->items()
                ->lockForUpdate()
                ->get();

            $existingItemsByFingerprint = [];
            foreach ($existingItems as $existingItem) {
                $key = $existingItem->sku_id.'_'.$existingItem->customization_fingerprint;
                $existingItemsByFingerprint[$key][] = $existingItem;
            }

            $createdItemsLog = [];
            $updatedItemsLog = [];
            $deletedItemsLog = [];

            $items = $input['items'] ?? [];
            $lineTotals = [];
            $currency = 'INR';

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

                $customSnapshot = $this->snapshots->publicCartSnapshot($item['customization_snapshot'] ?? []);
                $fingerprint = $this->snapshots->customizationFingerprint($item['customization_snapshot'] ?? []);
                $key = $sku->id.'_'.$fingerprint;

                if (! empty($existingItemsByFingerprint[$key])) {
                    $existingItem = array_shift($existingItemsByFingerprint[$key]);

                    $oldQuantity = $existingItem->quantity;
                    $newQuantity = $quantity;

                    if ($oldQuantity !== $newQuantity || $existingItem->unit_price_minor !== $unitPrice) {
                        $existingItem->update([
                            'quantity' => $newQuantity,
                            'unit_price_minor' => $unitPrice,
                            'line_subtotal_minor' => $lineTotal,
                            'line_total_minor' => $lineTotal,
                        ]);

                        $updatedItemsLog[] = [
                            'sku_code' => $sku->sku_code,
                            'quantity' => [
                                'old' => $oldQuantity,
                                'new' => $newQuantity,
                            ],
                            'unit_price' => [
                                'old' => $existingItem->getOriginal('unit_price_minor'),
                                'new' => $unitPrice,
                            ],
                        ];
                    }
                } else {
                    $priceSource = is_int($sku->price_minor) ? 'sku_price' : (is_int($product->base_price_minor) ? 'product_base_price' : 'unpriced');

                    $newItem = $order->items()->create([
                        'public_id' => 'IT-'.strtoupper(Str::random(12)),
                        'product_id' => $product->id,
                        'sku_id' => $sku->id,
                        'quantity' => $quantity,
                        'product_name_snapshot' => $product->name,
                        'product_slug_snapshot' => $product->slug,
                        'sku_code_snapshot' => $sku->sku_code,
                        'customization_fingerprint' => $fingerprint,
                        'customization_snapshot' => $customSnapshot,
                        'unit_price_minor' => $unitPrice,
                        'line_subtotal_minor' => $lineTotal,
                        'line_total_minor' => $lineTotal,
                        'currency' => $currency,
                        'price_source' => $priceSource,
                    ]);

                    $createdItemsLog[] = [
                        'sku_code' => $sku->sku_code,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ];
                }

                $lineTotals[] = (int) $lineTotal;
            }

            // Delete unmatched items
            foreach ($existingItemsByFingerprint as $key => $itemsList) {
                foreach ($itemsList as $deletedItem) {
                    $deletedItemsLog[] = [
                        'sku_code' => $deletedItem->sku_code_snapshot,
                        'quantity' => $deletedItem->quantity,
                        'unit_price' => $deletedItem->unit_price_minor,
                    ];
                    $deletedItem->delete();
                }
            }

            // Recalculate totals
            $discountAmount = isset($input['discount_amount_minor']) ? (int) $input['discount_amount_minor'] : 0;
            $shippingAmount = isset($input['shipping_amount_minor']) ? (int) $input['shipping_amount_minor'] : 0;
            $enableGst = $this->settingsService->get('tax', 'enable_gst', false);
            $taxAmount = $enableGst ? (isset($input['tax_amount_minor']) ? (int) $input['tax_amount_minor'] : 0) : 0;

            $totals = $this->totalsCalculator->fromLineTotals(
                $lineTotals,
                $discountAmount,
                $shippingAmount,
                $taxAmount,
            );

            // Fetch customer details if customer changed
            $customer = $order->customer;
            if ((int) $input['customer_id'] !== $order->customer_id) {
                $customer = Customer::query()->findOrFail($input['customer_id']);
            }

            $order->update([
                'customer_id' => $customer->id,
                'customer_snapshot' => [
                    'public_id' => $customer->public_id,
                    'name' => $customer->display_name ?? $customer->name,
                    'email' => $customer->email ?? null,
                ],
                'subtotal_amount_minor' => $totals->subtotalAmountMinor(),
                'discount_amount_minor' => $totals->discountAmountMinor(),
                'shipping_amount_minor' => $totals->shippingAmountMinor(),
                'tax_amount_minor' => $totals->taxAmountMinor(),
                'total_amount_minor' => $totals->totalAmountMinor(),
                'currency' => $currency,
                'internal_notes' => $input['internal_notes'] ?? $order->internal_notes,
            ]);

            // Compare post-save attributes to generate a detailed header diff
            $currentState = $order->fresh()->only($fieldsToTrack);
            $headerDiff = [];
            foreach ($fieldsToTrack as $field) {
                if ($originalState[$field] != $currentState[$field]) {
                    $headerDiff[$field] = [
                        'old' => $originalState[$field],
                        'new' => $currentState[$field],
                    ];
                }
            }

            // Build changes payload inside transaction
            $changesPayload = [
                'schema_version' => 1,
                'order_public_id' => $order->public_id,
                'customer_public_id' => $customer->public_id,
                'status' => $order->status,
                'subject_type' => 'order',
                'subject_id' => $order->public_id,
                'changes' => [
                    'header' => $headerDiff,
                    'items' => [
                        'created' => $createdItemsLog,
                        'updated' => $updatedItemsLog,
                        'deleted' => $deletedItemsLog,
                    ],
                ],
            ];

            // Register afterCommit callback
            DB::afterCommit(function () use ($actor, $changesPayload) {
                event(new AuditEvent('orders.order_edited', $actor, $changesPayload));
            });
        });

        return $order->fresh();
    }
}
