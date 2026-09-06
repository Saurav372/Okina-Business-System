<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProductSeoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product && $this->user()?->can('manageSeo', $product);
    }

    protected function prepareForValidation(): void
    {
        $product = $this->route('product');
        $rawSlug = $this->input('slug');

        if ($rawSlug !== null && trim((string) $rawSlug) !== '') {
            $normalizedSlug = Str::slug((string) $rawSlug);
        } elseif ($product) {
            $normalizedSlug = $product->slug;
        } else {
            $normalizedSlug = '';
        }

        $this->merge([
            'slug' => $normalizedSlug,
            'robots_index' => $this->boolean('robots_index'),
            'robots_follow' => $this->boolean('robots_follow'),
        ]);
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $productId = $product ? $product->id : null;

        return [
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'focus_keyword' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'robots_index' => ['boolean'],
            'robots_follow' => ['boolean'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:1000'],
            'og_image_id' => ['nullable', 'exists:files,id'],
            'twitter_title' => ['nullable', 'string', 'max:255'],
            'twitter_description' => ['nullable', 'string', 'max:1000'],
            'twitter_image_id' => ['nullable', 'exists:files,id'],
        ];
    }
}
