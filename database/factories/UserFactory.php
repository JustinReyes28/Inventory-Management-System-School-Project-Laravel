<?php

namespace Database\Factories;

use App\Enums\RoleId;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'password_hash' => static::$password ??= Hash::make('password'),
            'role_id' => RoleId::EMPLOYEE->value,
            'created_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_id' => RoleId::ADMIN->value,
        ]);
    }

    public function employee(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_id' => RoleId::EMPLOYEE->value,
        ]);
    }
}
