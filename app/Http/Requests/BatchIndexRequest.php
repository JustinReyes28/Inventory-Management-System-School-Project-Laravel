<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $itemId = $this->input('item_id', $this->input('item'));

        $this->merge([
            'search' => $this->string('search')->trim()->toString(),
            'item_id' => $itemId,
            'status' => $this->input('status', ''),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'item_id' => [
                'nullable',
                'integer',
                Rule::exists('items', 'id')->where(
                    fn ($query) => $query->where('is_deleted', false),
                ),
            ],
            'status' => ['nullable', Rule::in(['safe', 'near_expiry', 'expired'])],
        ];
    }
}
