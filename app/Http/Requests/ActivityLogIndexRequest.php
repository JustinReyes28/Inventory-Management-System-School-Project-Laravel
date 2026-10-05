<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'page' => max(1, (int) $this->input('page', 1)),
            'per_page' => (int) $this->input('per_page', 20),
            'action_type' => $this->input('action_type', $this->input('action')),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action_type' => ['nullable', 'string', Rule::in([
                'create',
                'update',
                'delete',
                'stock_in',
                'stock_out',
            ])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }
}
