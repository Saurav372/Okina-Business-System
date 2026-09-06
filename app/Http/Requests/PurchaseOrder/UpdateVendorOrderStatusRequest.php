<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorOrderStatusRequest extends FormRequest
{
    /**
     * Named error bag for status transition forms.
     */
    protected $errorBag = 'status';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required_without:payment_status', 'nullable', 'string', 'in:draft,submitted,ordered,approved,cancelled,partially_received,received'],
            'payment_status' => ['required_without:status', 'nullable', 'string', 'in:unpaid,partially_paid,paid,refunded'],
        ];
    }
}
