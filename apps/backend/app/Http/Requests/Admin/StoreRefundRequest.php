<?php

namespace App\Http\Requests\Admin;

use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gated by policy in controller
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('payment_id') && (! $this->has('order_public_id') || ! $this->has('refund_type'))) {
            $payment = Payment::with('order')->find($this->input('payment_id'));
            if ($payment) {
                if (! $this->has('order_public_id') && $payment->order) {
                    $this->merge(['order_public_id' => $payment->order->public_id]);
                }
                if (! $this->has('refund_type')) {
                    $type = ($this->integer('amount_minor') === $payment->amount_minor) ? Refund::TYPE_FULL : Refund::TYPE_PARTIAL;
                    $this->merge(['refund_type' => $type]);
                }
            }
        }
    }

    public function rules(): array
    {
        return [
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'order_public_id' => ['nullable', 'string', 'exists:orders,public_id'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'refund_type' => ['nullable', 'string', Rule::in([Refund::TYPE_FULL, Refund::TYPE_PARTIAL])],
            'reason_code' => ['required', 'string', 'max:80'],
            'reason_note' => ['nullable', 'string', 'max:1000'],
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

                if ($payment) {
                    if ($payment->status !== Payment::STATUS_SUCCEEDED) {
                        $validator->errors()->add('payment_id', 'Only succeeded payments can be refunded.');

                        return;
                    }
                }
            },
        ];
    }
}
