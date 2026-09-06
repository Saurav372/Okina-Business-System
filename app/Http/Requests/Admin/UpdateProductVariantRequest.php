<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Product $product */
        $product = $this->route('product');
        $variant = $this->route('variant');

        return $this->user()->can('update', $product) && $variant->product_id === $product->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product')->id;
        $variantId = $this->route('variant')->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('product_variants', 'code')->where('product_id', $productId)->ignore($variantId),
            ],
            'display_type' => ['required', Rule::in(['select', 'swatch', 'button', 'radio'])],
            'values_csv' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $items = array_filter(array_map('trim', explode(',', $value)));
                    if (empty($items)) {
                        $fail('The variant option must have at least one non-empty value.');
                    }
                },
            ],
            'is_required' => ['boolean'],
            'sort_order' => ['required', 'integer'],
        ];
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->filled('code') ? Str::slug($this->input('code')) : null,
            'is_required' => $this->has('is_required'),
        ]);
    }
}
