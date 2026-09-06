<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Enums\VendorPaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorPaymentRequest extends FormRequest
{
    /**
     * Named error bag for payment modal forms.
     */
    protected $errorBag = 'payment';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return $user->hasPermissionTo('purchases.pay')
            || $user->hasPermissionTo('purchases.manage')
            || $user->hasPermissionTo('inventory.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount_minor' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', Rule::enum(VendorPaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:160'],
            'paid_at' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
