<?php

namespace Database\Seeders;

use App\Enums\RoleId;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call(RoleSeeder::class);

        foreach ([
            ['username' => 'employee', 'full_name' => 'Demo Employee', 'password' => 'Employee@1234'],
            ['username' => 'employee2', 'full_name' => 'Demo Employee 2', 'password' => 'Employee2@1234'],
        ] as $account) {
            User::query()->firstOrCreate(
                ['username' => $account['username']],
                [
                    'full_name' => $account['full_name'],
                    'password_hash' => Hash::make($account['password']),
                    'role_id' => RoleId::EMPLOYEE->value,
                ],
            );
        }
    }
}
