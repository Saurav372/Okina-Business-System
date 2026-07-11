<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreProductMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $this->user()->can('update', $product);
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Please select at least one image to upload.',
            'images.max' => 'You may upload a maximum of 10 images at a time.',
            'images.*.image' => 'Each file must be an image (jpg, jpeg, png, gif, or webp).',
            'images.*.mimes' => 'Accepted formats: jpg, jpeg, png, gif, webp.',
            'images.*.max' => 'Each image may not exceed 10 MB.',
        ];
    }

    /**
     * Redirect back to the Media tab when validation fails,
     * so the user does not have to navigate back to it manually.
     */
    protected function failedValidation(Validator $validator): void
    {
        /** @var Product $product */
        $product = $this->route('product');

        throw new HttpResponseException(
            redirect()
                ->route('admin.products.edit', [$product, 'tab' => 'media'])
                ->withErrors($validator)
                ->withInput()
        );
    }
}
