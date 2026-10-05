<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'batch_number' => $this->string('batch_number')->trim()->toString(),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'item_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('items', 'id')->where(
                    fn ($query) => $query->where('is_deleted', false),
                ),
            ],
            'batch_number' => ['bail', 'required', 'string', 'max:50'],
            'quantity' => ['bail', 'required', 'integer', 'min:0', 'max:2147483647'],
            'expiry_date' => ['bail', 'required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'item_id' => 'item',
        ];
    }
}
