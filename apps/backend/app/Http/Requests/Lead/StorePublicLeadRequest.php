<?php

namespace App\Http\Requests\Lead;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePublicLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact_name' => ['required', 'string', 'max:160'],
            'email' => ['required_without:phone', 'nullable', 'email', 'max:180'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:40'],
            'interest_summary' => ['nullable', 'string', 'max:300'],
            'requirements' => ['nullable', 'string'],
            'product_interest' => ['nullable', 'array'],
            'product_interest.*' => ['string', 'max:120'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:160'],
            'utm_content' => ['nullable', 'string', 'max:160'],
            'utm_term' => ['nullable', 'string', 'max:160'],
            'referrer_url' => ['nullable', 'url', 'max:2048'],
            'landing_page_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
