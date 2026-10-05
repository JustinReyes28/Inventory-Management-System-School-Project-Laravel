<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->can($this->isMethod('POST') ? 'create users' : 'update users');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => $this->string('full_name')->trim()->toString(),
            'username' => $this->string('username')->trim()->toString(),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'full_name' => ['bail', 'required', 'string', 'max:100'],
            'username' => [
                'bail',
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
                Rule::unique('users', 'username')
                    ->ignore($user)
                    ->where(fn ($query) => $query->whereRaw(
                        'LOWER(username) = LOWER(?)',
                        [(string) $this->input('username')],
                    )),
            ],
            'password' => [
                'sometimes',
                'nullable',
                Rule::requiredIf($this->isMethod('POST')),
                'string',
                'min:6',
                'max:255',
            ],
            // Role changes are validated Spatie operations: the id must exist
            // in the Spatie roles table and is applied via syncRoles().
            'role_id' => ['bail', 'required', 'integer', 'exists:roles,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'role_id' => 'role',
        ];
    }
}
