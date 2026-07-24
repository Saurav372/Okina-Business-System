<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $paidSum = (int) ($this->payments_sum_amount_minor ?? 0);
        $totalAmount = (int) $this->total_amount_minor;

        // Payment status derivation logic
        if ($paidSum >= $totalAmount && $totalAmount > 0) {
            $paymentStatus = 'Paid';
        } elseif ($paidSum > 0) {
            $paymentStatus = 'Partially Paid';
        } else {
            $paymentStatus = 'Unpaid';
        }

        // Map customer info from snapshot
        $customerSnapshot = $this->customer_snapshot ?? [];
        $customerName = data_get($customerSnapshot, 'name', 'N/A');
        $customerPhone = data_get($customerSnapshot, 'phone', 'N/A');
        $customerEmail = data_get($customerSnapshot, 'email', 'N/A');

        // Look up pretty name for order source from config
        $sourcesConfig = config('orders.sources', []);
        $orderSourceLabel = $sourcesConfig[$this->order_source] ?? ucfirst($this->order_source);

        return [
            'public_id' => $this->public_id,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_email' => $customerEmail,
            'order_source' => $this->order_source,
            'order_source_label' => $orderSourceLabel,
            'status' => $this->status,
            'payment_status' => $paymentStatus,
            'total_amount_minor' => $totalAmount,
            'total_amount_formatted' => '₹'.number_format($totalAmount / 100, 2),
            'design_approved' => (bool) $this->design_approved,
            'created_at' => $this->created_at?->toIso8601String(),
            'placed_at' => $this->placed_at?->toIso8601String(),
        ];
    }
}
