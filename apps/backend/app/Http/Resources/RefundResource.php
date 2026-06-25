<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'refund_type' => $this->refund_type,
            'status' => $this->status,
            'amount_minor' => $this->amount_minor,
            'currency' => $this->currency,
            'reason_code' => $this->reason_code,
            'reason_note' => $this->reason_note,
            'provider_refund_id' => $this->provider_refund_id,
            'provider_payment_id' => $this->provider_payment_id,
            'provider_reference' => $this->provider_reference,
            'requested_at' => $this->requested_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'order_public_id' => $this->whenLoaded('order', fn () => $this->order->public_id),
            'payment_receipt_number' => $this->whenLoaded('payment', fn () => $this->payment->receipt_number),
        ];
    }
}
