<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Refund::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Auto-convert amount_rupees to amount_minor if amount_minor is missing, empty, or zero
        if ((! $this->filled('amount_minor') || $this->integer('amount_minor') <= 0) && $this->filled('amount_rupees')) {
            $cleaned = str_replace(',', '', (string) $this->input('amount_rupees'));
            if (is_numeric($cleaned)) {
                $this->merge([
                    'amount_minor' => (int) round(((float) $cleaned) * 100),
                ]);
            }
        }

        if ($this->has('payment_id') && (! $this->has('order_public_id') || ! $this->has('refund_type'))) {
            $payment = Payment::with('order')->find($this->input('payment_id'));
            if ($payment) {
                if (! $this->has('order_public_id') && $payment->order) {
                    $this->merge(['order_public_id' => $payment->order->public_id]);
                }
                if (! $this->has('refund_type')) {
                    $existingRefundedSum = (int) Refund::query()
                        ->where('payment_id', $payment->id)
                        ->whereIn('status', [Refund::STATUS_REQUESTED, Refund::STATUS_APPROVED, Refund::STATUS_PROCESSING, Refund::STATUS_SUCCEEDED])
                        ->sum('amount_minor');

                    $maxRefundable = $payment->amount_minor - $existingRefundedSum;
                    $amountMinor = $this->integer('amount_minor');

                    $type = ($amountMinor === $maxRefundable && $existingRefundedSum === 0)
                        ? Refund::TYPE_FULL
                        : Refund::TYPE_PARTIAL;

                    $this->merge(['refund_type' => $type]);
                }
            }
        }

        if (! $this->filled('reason_code')) {
            $this->merge(['reason_code' => 'customer_cancellation']);
        }
    }

    public function rules(): array
    {
        return [
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'order_public_id' => ['nullable', 'string', 'exists:orders,public_id'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'amount_rupees' => ['nullable', 'numeric', 'min:0.01'],
            'refund_type' => ['nullable', 'string', Rule::in([Refund::TYPE_FULL, Refund::TYPE_PARTIAL])],
            'reason_code' => ['nullable', 'string', 'max:80'],
            'reason_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_id.required' => 'Please select a succeeded payment to refund.',
            'payment_id.exists' => 'The selected payment could not be found.',
            'amount_minor.required' => 'Please specify a valid refund amount.',
            'amount_minor.min' => 'The refund amount must be at least ₹0.01.',
            'amount_rupees.min' => 'The refund amount must be at least ₹0.01.',
            'reason_code.required' => 'Please select a reason code for the refund.',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->any()) {
                    return;
                }

                $payment = Payment::find($this->input('payment_id'));

                if ($this->has('order_public_id')) {
                    $order = Order::where('public_id', $this->input('order_public_id'))->first();
                    if ($order && $payment && $payment->order_id !== $order->id) {
                        $validator->errors()->add('payment_id', 'The payment does not belong to the specified order.');

                        return;
                    }
                }

                if ($payment) {
                    if ($payment->status !== Payment::STATUS_SUCCEEDED) {
                        $validator->errors()->add('payment_id', 'Only succeeded payments can be refunded.');

                        return;
                    }

                    if ($this->integer('amount_minor') > $payment->amount_minor) {
                        $validator->errors()->add('amount_minor', 'The refund amount cannot exceed the payment amount.');

                        return;
                    }

                    $existingRefundedSum = (int) Refund::query()
                        ->where('payment_id', $payment->id)
                        ->whereIn('status', [Refund::STATUS_REQUESTED, Refund::STATUS_APPROVED, Refund::STATUS_PROCESSING, Refund::STATUS_SUCCEEDED])
                        ->sum('amount_minor');

                    $maxRefundable = $payment->amount_minor - $existingRefundedSum;
                    if ($this->integer('amount_minor') > $maxRefundable) {
                        $validator->errors()->add('amount_minor', 'Refund amount exceeds remaining refundable balance.');

                        return;
                    }

                    if ($this->input('refund_type') === Refund::TYPE_FULL && $this->integer('amount_minor') !== $maxRefundable) {
                        $validator->errors()->add('amount_minor', 'Full refund requires exact remaining refundable balance.');

                        return;
                    }
                }
            },
        ];
    }
}
