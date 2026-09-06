<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickCustomerCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('orders.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'brand_name' => ['required', 'string', 'max:150'],
            'phones' => ['required', 'array', 'min:1'],
            'phones.*' => ['required', 'string', 'max:25'],
            'same_as_whatsapp' => ['boolean'],
            'whatsapp_phone' => ['nullable', 'string', 'max:25'],
            'lead_source' => ['required', 'string', Rule::in([
                'instagram',
                'whatsapp',
                'walk_in',
                'phone',
                'referral',
                'other',
            ])],
            'email' => ['nullable', 'email', 'max:120'],
            
            // Shipping Address
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.line_1' => ['nullable', 'string', 'max:200'],
            'shipping_address.line_2' => ['nullable', 'string', 'max:200'],
            'shipping_address.city' => ['nullable', 'string', 'max:100'],
            'shipping_address.state' => ['nullable', 'string', 'max:100'],
            'shipping_address.postal_code' => ['nullable', 'string', 'max:20'],

            // Billing Address
            'same_as_billing' => ['boolean'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.line_1' => ['nullable', 'string', 'max:200'],
            'billing_address.line_2' => ['nullable', 'string', 'max:200'],
            'billing_address.city' => ['nullable', 'string', 'max:100'],
            'billing_address.state' => ['nullable', 'string', 'max:100'],
            'billing_address.postal_code' => ['nullable', 'string', 'max:20'],
            'billing_address.gstin' => ['nullable', 'string', 'max:20'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Filter out any blank phone numbers
        if ($this->has('phones') && is_array($this->phones)) {
            $filteredPhones = array_values(array_filter(array_map('trim', $this->phones)));
            $this->merge(['phones' => $filteredPhones]);
        }

        $this->merge([
            'same_as_whatsapp' => filter_var($this->same_as_whatsapp, FILTER_VALIDATE_BOOLEAN),
            'same_as_billing' => filter_var($this->same_as_billing, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
