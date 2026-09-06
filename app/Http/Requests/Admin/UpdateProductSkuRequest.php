<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductSkuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Ownership verification (SKU belongs to product) is handled natively
     * by scopeBindings() on the route, which returns 404 for mismatches.
     */
    public function authorize(): bool
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $this->user()->can('update', $product);
    }

    public function rules(): array
    {
        $skuId = $this->route('sku')->id;

        return [
            'sku_code' => ['required', 'string', 'max:100', Rule::unique('product_skus', 'sku_code')->ignore($skuId)->whereNull('deleted_at')],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('product_skus', 'barcode')->ignore($skuId)->whereNull('deleted_at')],
            'status' => ['required', Rule::in(['active', 'out_of_stock', 'inactive'])],
            'direct_checkout_enabled' => ['boolean'],
            'quote_required' => ['boolean'],
            'price_minor' => ['required', 'integer', 'min:0'],
            'compare_at_price_minor' => ['nullable', 'integer', 'min:0'],
            'track_stock' => ['boolean'],
            'stock_quantity' => ['required_if:track_stock,1', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'allow_backorder' => ['boolean'],
            'weight_grams' => ['nullable', 'integer', 'min:0'],
            'length_mm' => ['nullable', 'integer', 'min:0'],
            'width_mm' => ['nullable', 'integer', 'min:0'],
            'height_mm' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['required', 'integer'],
        ];
    }

    /**
     * Normalize checkbox inputs before validation.
     *
     * HTML checkbox inputs are normalized before validation so unchecked
     * values persist as false rather than being absent from the payload.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'direct_checkout_enabled' => $this->boolean('direct_checkout_enabled'),
            'quote_required' => $this->boolean('quote_required'),
            'track_stock' => $this->boolean('track_stock'),
            'allow_backorder' => $this->boolean('allow_backorder'),
        ]);
    }
}
