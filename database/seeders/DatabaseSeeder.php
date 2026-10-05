<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $username = trim((string) env('ADMIN_USERNAME', 'admin'));
        $password = env('ADMIN_PASSWORD');
        $admin = User::query()->firstOrNew(['username' => $username]);

        // Usernames are editable and publicly registrable. Only reuse an
        // account that already has the administrator role.
        if ($admin->exists && ! $admin->hasRole(RoleName::ADMIN->value)) {
            throw new \RuntimeException("Cannot seed administrator: username [{$username}] belongs to a non-admin account. Review ADMIN_USERNAME before provisioning the administrator.");
        }

        if (! $admin->exists) {
            if (app()->environment('production') && (! is_string($password) || $password === '')) {
                throw new \RuntimeException('ADMIN_PASSWORD must be set before seeding in production.');
            }

            $admin->full_name = 'System Administrator';
            $admin->email = 'admin@example.test';
            $admin->password_hash = Hash::make($password ?? 'Admin@1234');
            $admin->save();
            $admin->assignRole(RoleName::ADMIN->value);
        }

        $this->call(EmployeeSeeder::class);
    }
}
