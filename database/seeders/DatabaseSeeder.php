<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $username = trim((string) env('ADMIN_USERNAME', 'admin'));
        $password = env('ADMIN_PASSWORD');
        $admin = User::query()->firstOrNew(['username' => $username]);
        $admin->full_name = 'System Administrator';

        if (! $admin->exists) {
            if (app()->environment('production') && (! is_string($password) || $password === '')) {
                throw new \RuntimeException('ADMIN_PASSWORD must be set before seeding in production.');
            }

            $admin->password_hash = Hash::make($password ?? 'Admin@1234');
        }

        $admin->save();
        $admin->assignRole(RoleName::ADMIN->value);

        $this->call(EmployeeSeeder::class);
    }
}
