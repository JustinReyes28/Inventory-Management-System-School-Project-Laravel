<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'category_name' => [
                'bail',
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'category_name')
                    ->ignore($category)
                    ->where(fn ($query) => $query->whereRaw(
                        'LOWER(category_name) = LOWER(?)',
                        [(string) $this->input('category_name')],
                    )),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->filled('category_name')
            ? $this->input('category_name')
            : $this->input('name');

        $this->merge([
            'category_name' => is_string($name) ? trim($name) : $name,
        ]);
    }
}
