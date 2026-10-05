<?php

namespace Database\Factories;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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
            'created_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->withRole(RoleName::ADMIN->value);
    }

    public function employee(): static
    {
        return $this->withRole(RoleName::EMPLOYEE->value);
    }

    public function user(): static
    {
        return $this->withRole(RoleName::USER->value);
    }

    /**
     * Assign a Spatie role after creation (roles are seeded lazily so the
     * factory works on a fresh test database).
     */
    private function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            $user->assignRole(Role::findOrCreate($role, 'web'));
        });
    }
}
