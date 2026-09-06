<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'amount_minor' => $this->amount_minor,
            'currency' => $this->currency,
            'status' => $this->status,
            'provider' => $this->provider,
            'method' => $this->method,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'provider_payment_id' => $this->provider_payment_id,
            'provider_order_id' => $this->provider_order_id,
            'provider_reference' => $this->provider_reference,
            'notes' => $this->notes,
            'order_public_id' => $this->whenLoaded('order', fn () => $this->order->public_id),
            'gateway_fee_minor' => $this->when(
                $request->user()?->can('viewSensitive', $this->resource),
                $this->gateway_fee_minor
            ),
            'net_amount_minor' => $this->when(
                $request->user()?->can('viewSensitive', $this->resource),
                $this->net_amount_minor
            ),
        ];
    }
}
