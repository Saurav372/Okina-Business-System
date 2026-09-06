<?php

namespace App\Http\Requests\Admin;

use App\Models\StoredFile;
use Illuminate\Foundation\Http\FormRequest;

class SalesOrderCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku_code' => ['required', 'string', 'max:80'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.customization_snapshot' => ['nullable', 'array'],
            'items.*.customization_snapshot.print_methods' => ['nullable', 'array'],
            'items.*.customization_snapshot.print_methods.*' => ['string', 'max:50'],
            'items.*.customization_snapshot.production_notes' => ['nullable', 'string', 'max:2000'],
            'items.*.customization_snapshot.mockup' => ['nullable', 'array'],
            'items.*.customization_snapshot.mockup.stored_file_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $file = StoredFile::query()
                            ->where('id', $value)
                            ->whereIn('file_kind', [StoredFile::KIND_MOCKUP, StoredFile::KIND_PROOF])
                            ->whereNull('deleted_at')
                            ->first();

                        if (!$file) {
                            $fail('The selected mockup file is invalid or no longer exists.');
                            return;
                        }

                        if ($file->uploaded_by_user_id !== $this->user()?->id && !$this->user()?->can('orders.manage')) {
                            $fail('You do not have permission to attach this mockup file.');
                        }
                    }
                },
            ],
            'advance_payment' => ['nullable', 'array'],
            'advance_payment.amount_minor' => ['required_with:advance_payment', 'integer', 'min:0'],
            'advance_payment.due_date' => ['required_with:advance_payment', 'date'],
            'discount_amount_minor' => ['nullable', 'integer', 'min:0'],
            'shipping_amount_minor' => ['nullable', 'integer', 'min:0'],
            'tax_amount_minor' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
