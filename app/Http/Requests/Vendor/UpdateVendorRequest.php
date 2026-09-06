<?php

namespace App\Http\Requests\Vendor;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize inputs before validation rules execute.
     */
    protected function prepareForValidation(): void
    {
        $merge = [
            'gstin' => filled($this->gstin) ? strtoupper(trim($this->gstin)) : null,
            'country_code' => strtoupper(trim($this->country_code ?: 'IN')),
        ];

        if ($this->has('vendor_code')) {
            $merge['vendor_code'] = filled($this->vendor_code) ? strtoupper(trim($this->vendor_code)) : null;
        }

        $this->merge($merge);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Vendor|string|int $vendor */
        $vendor = $this->route('vendor');
        $vendorId = $vendor instanceof Vendor ? $vendor->id : (int) $vendor;

        return [
            'name' => ['required', 'string', 'max:180'],
            'vendor_code' => ['nullable', 'string', 'max:60', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', Rule::unique('vendors', 'vendor_code')->ignore($vendorId)],
            'status' => ['nullable', 'string', Rule::enum(VendorStatus::class)],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'gstin' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', Rule::unique('vendors', 'gstin')->ignore($vendorId)],
            'payment_terms' => ['nullable', 'string', 'max:120'],
            'address_line1' => ['nullable', 'string', 'max:180'],
            'address_line2' => ['nullable', 'string', 'max:180'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
