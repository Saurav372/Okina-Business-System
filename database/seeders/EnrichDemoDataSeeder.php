<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\ProductSku;
use Illuminate\Database\Seeder;

class EnrichDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $skus = ProductSku::with('product')->get();
        if ($skus->isEmpty()) {
            return;
        }

        $orders = Order::with(['items', 'payments'])->get();

        foreach ($orders as $order) {
            if ($order->items->count() === 0) {
                $sku = $skus->random();
                $qty = rand(1, 4);
                $price = $sku->price_minor ?? $sku->product->base_price_minor ?? 69900;
                $lineTotal = $price * $qty;
                $snapshot = [
                    'print_position' => 'front',
                    'print_method' => 'dtf',
                    'customer_note' => 'Demo sample print note',
                ];
                
                $order->items()->create([
                    'product_id' => $sku->product_id,
                    'sku_id' => $sku->id,
                    'quantity' => $qty,
                    'product_name_snapshot' => $sku->product->name,
                    'product_slug_snapshot' => $sku->product->slug,
                    'sku_code_snapshot' => $sku->sku_code,
                    'unit_price_minor' => $price,
                    'line_subtotal_minor' => $lineTotal,
                    'line_total_minor' => $lineTotal,
                    'price_source' => 'catalog',
                    'currency' => 'INR',
                    'customization_fingerprint' => sha1(json_encode($snapshot)),
                    'customization_snapshot' => $snapshot,
                ]);

                $order->subtotal_amount_minor = $lineTotal;
                $order->tax_amount_minor = 0;
                $order->shipping_amount_minor = 9900;
                $order->total_amount_minor = $order->subtotal_amount_minor + $order->shipping_amount_minor;
                $order->save();
            }

            if ($order->payments->count() === 0) {
                $status = is_object($order->status) ? $order->status->value : (string) $order->status;
                if (in_array($status, ['confirmed', 'in_production', 'ready_to_ship', 'shipped', 'delivered'], true)) {
                    $order->payments()->create([
                        'receipt_number' => 'PAY-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
                        'amount_minor' => $order->total_amount_minor,
                        'net_amount_minor' => $order->total_amount_minor,
                        'currency' => 'INR',
                        'provider' => 'manual',
                        'method' => 'upi',
                        'payment_type' => 'manual',
                        'status' => 'succeeded',
                        'recorded_by_user_id' => 1,
                        'paid_at' => $order->placed_at ?? now(),
                        'notes' => 'Settled via UPI transfer',
                    ]);
                }
            }
        }
    }
}
