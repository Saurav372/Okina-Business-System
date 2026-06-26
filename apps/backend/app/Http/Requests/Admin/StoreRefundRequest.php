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
        return true; // Gated by policy in controller
    }

    public function rules(): array
    {
        return [
            'order_public_id' => ['required', 'string', 'exists:orders,public_id'],
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'refund_type' => ['required', 'string', Rule::in([Refund::TYPE_FULL, Refund::TYPE_PARTIAL])],
            'reason_code' => ['nullable', 'string', 'max:80'],
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

                $order = Order::where('public_id', $this->input('order_public_id'))->first();
                $payment = Payment::find($this->input('payment_id'));

                if ($order && $payment) {
                    if ($payment->order_id !== $order->id) {
                        $validator->errors()->add('payment_id', 'The payment does not belong to the specified order.');

                        return;
                    }

                    if ($payment->status !== Payment::STATUS_SUCCEEDED) {
                        $validator->errors()->add('payment_id', 'Only succeeded payments can be refunded.');

                        return;
                    }

                    if ($this->input('amount_minor') > $payment->amount_minor) {
                        $validator->errors()->add('amount_minor', 'The refund amount cannot exceed the payment amount.');

                        return;
                    }
                }
            },
        ];
    }
}
