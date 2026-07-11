<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkOrderActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('orders.manage');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['confirm', 'cancel'])],
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('order_ids')) {
            $this->merge([
                'order_ids' => collect($this->order_ids)
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
            ]);
        }
    }
}
