<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $this->user()->can('update', $product);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'primary_category_id' => [
                'nullable',
                Rule::exists('product_categories', 'id')->where('status', 'active'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($this->route('product')->id)],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'product_type' => ['required', Rule::in([Product::TYPE_SIMPLE, Product::TYPE_VARIABLE, Product::TYPE_BUNDLE])],
            'customization_mode' => ['required', Rule::in([Product::CUSTOMIZATION_NONE, Product::CUSTOMIZATION_OPTIONAL, Product::CUSTOMIZATION_REQUIRED])],
            'fulfillment_type' => ['required', Rule::in([Product::FULFILLMENT_STOCKED, Product::FULFILLMENT_MADE_TO_ORDER])],
            'status' => ['required', Rule::in([Product::STATUS_DRAFT, Product::STATUS_ACTIVE, Product::STATUS_OUT_OF_STOCK, Product::STATUS_BULK_ONLY, Product::STATUS_DISCONTINUED])],
            'visibility' => ['required', Rule::in([Product::VISIBILITY_PUBLIC, Product::VISIBILITY_PRIVATE])],
            'direct_checkout_enabled' => ['boolean'],
            'quote_enabled' => ['boolean'],
            'min_order_quantity' => ['required', 'integer', 'min:1'],
            'max_order_quantity' => ['nullable', 'integer', 'min:1', 'gte:min_order_quantity'],
            'bulk_threshold_quantity' => ['nullable', 'integer', 'min:1'],
            'base_price_minor' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', Rule::in(['INR'])],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer'],
            'published_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->filled('slug') ? Str::slug($this->input('slug')) : null,
            'direct_checkout_enabled' => $this->has('direct_checkout_enabled'),
            'quote_enabled' => $this->has('quote_enabled'),
        ]);
    }
}
