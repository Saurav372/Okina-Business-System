<?php

namespace App\Http\Requests\Admin;

use App\Models\Quotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lead_public_id' => ['nullable', 'string', 'exists:leads,public_id'],
            'customer_public_id' => ['nullable', 'string', 'exists:customers,public_id'],
            'contact_name' => ['nullable', 'string', 'max:160'],
            'company_name' => ['nullable', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'quotation_type' => ['required', 'string', Rule::in(Quotation::TYPES)],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'customer_note' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'discount_amount_minor' => ['nullable', 'integer', 'min:0'],
            'shipping_amount_minor' => ['nullable', 'integer', 'min:0'],
            'tax_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku_code' => ['nullable', 'string', 'max:80', 'exists:product_skus,sku_code'],
            'items.*.item_name' => ['required', 'string', 'max:180'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price_minor' => ['nullable', 'integer', 'min:0'],
            'items.*.customization_snapshot' => ['nullable', 'array'],
            'items.*.customer_note' => ['nullable', 'string'],
            'items.*.internal_notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hasLead = $this->filled('lead_public_id');
            $hasCustomer = $this->filled('customer_public_id');
            $hasContactName = $this->filled('contact_name');

            $sourcesCount = ($hasLead ? 1 : 0) + ($hasCustomer ? 1 : 0) + ($hasContactName ? 1 : 0);

            if ($sourcesCount === 0) {
                $validator->errors()->add('source', 'Either lead_public_id, customer_public_id, or contact_name must be provided.');
            } elseif ($sourcesCount > 1) {
                $validator->errors()->add('source', 'Only one quotation source (lead, customer, or manual contact) can be provided.');
            }

            // If lead or customer is supplied, block manual contact fields
            if ($hasLead || $hasCustomer) {
                if ($this->filled('contact_name') || $this->filled('email') || $this->filled('phone') || $this->filled('company_name')) {
                    $validator->errors()->add('source', 'Manual contact details cannot be supplied when a lead or customer is linked.');
                }
            }

            // If manual contact_name is supplied, require email or phone
            if ($hasContactName) {
                if (! $this->filled('email') && ! $this->filled('phone')) {
                    $validator->errors()->add('contact_name', 'At least one contact method (email or phone) is required for manual quotations.');
                }
            }
        });
    }
}
