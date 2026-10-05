<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $categoryId = $this->input('category_id', $this->input('category'));

        $this->merge([
            'search' => $this->string('search')->trim()->toString(),
            'category_id' => $categoryId,
            'low_stock' => $this->boolean('low_stock'),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'low_stock' => ['nullable', 'boolean'],
        ];
    }
}
