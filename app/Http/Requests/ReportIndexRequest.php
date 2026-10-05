<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $requestedTab = (string) $this->input('tab', 'low_stock');
        $tab = match ($requestedTab) {
            '', 'low-stock' => 'low_stock',
            'activity', 'activity-summary' => 'activity_summary',
            default => $requestedTab,
        };

        $this->merge([
            'tab' => $tab,
            'category_id' => $this->input('category_id', $this->input('category')),
            'days' => (int) $this->input('days', 30),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'tab' => ['required', Rule::in(['low_stock', 'expiry', 'activity_summary'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'days' => ['required', 'integer', 'min:1', 'max:90'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }
}
