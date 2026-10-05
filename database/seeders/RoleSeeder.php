<?php

namespace Database\Seeders;

use App\Enums\RoleId;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleId::cases() as $role) {
            Role::query()->updateOrCreate(
                ['id' => $role->value],
                ['role_name' => $role->label()],
            );
        }
    }
}
