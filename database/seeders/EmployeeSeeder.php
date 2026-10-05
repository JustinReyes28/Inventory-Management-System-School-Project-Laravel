<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local/demo accounts used to demonstrate the permission differences between
 * the Employee and User roles. Never seeded outside local/testing.
 */
class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call(RolesAndPermissionsSeeder::class);

        foreach ([
            ['username' => 'employee', 'full_name' => 'Demo Employee', 'email' => 'employee@example.test', 'password' => 'Employee@1234', 'role' => RoleName::EMPLOYEE->value],
            ['username' => 'employee2', 'full_name' => 'Demo Employee 2', 'email' => 'employee2@example.test', 'password' => 'Employee2@1234', 'role' => RoleName::EMPLOYEE->value],
            ['username' => 'viewer', 'full_name' => 'Demo Viewer', 'email' => 'viewer@example.test', 'password' => 'Viewer@1234', 'role' => RoleName::USER->value],
        ] as $account) {
            $user = User::query()->firstOrCreate(
                ['username' => $account['username']],
                [
                    'full_name' => $account['full_name'],
                    'email' => $account['email'],
                    'password_hash' => Hash::make($account['password']),
                ],
            );

            $user->assignRole($account['role']);
        }
    }
}
