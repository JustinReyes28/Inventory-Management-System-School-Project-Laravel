<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => $this->string('sku')->trim()->toString(),
            'name' => $this->string('name')->trim()->toString(),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $item = $this->route('item');

        return [
            'sku' => [
                'bail',
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
                Rule::unique('items', 'sku')
                    ->ignore($item)
                    ->where(fn ($query) => $query->whereRaw(
                        'LOWER(sku) = LOWER(?)',
                        [(string) $this->input('sku')],
                    )),
            ],
            'name' => ['bail', 'required', 'string', 'max:150'],
            'category_id' => ['bail', 'required', 'integer', 'exists:categories,id'],
            'price' => ['bail', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'quantity' => ['bail', 'required', 'integer', 'min:0', 'max:2147483647'],
            'low_stock_threshold' => ['bail', 'required', 'integer', 'min:0', 'max:2147483647'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'low_stock_threshold' => 'low stock threshold',
        ];
    }
}
