<?php

namespace App\Actions\Fortify;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * Role and permission fields are never read from the input (see the
     * Arr::only call), so registration can only ever create a User-role
     * account. The password is stored in the legacy password_hash column.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $input = Arr::only($input, [
            'full_name',
            'username',
            'email',
            'password',
            'password_confirmation',
        ]);

        Validator::make($input, [
            'full_name' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
                Rule::unique('users', 'username'),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'full_name' => $input['full_name'],
            'username' => $input['username'],
            'email' => $input['email'],
            'password_hash' => Hash::make($input['password']),
        ]);

        // The User role is the default for public registration.
        $user->assignRole(Role::findOrCreate(RoleName::USER->value, 'web'));

        return $user;
    }
}
