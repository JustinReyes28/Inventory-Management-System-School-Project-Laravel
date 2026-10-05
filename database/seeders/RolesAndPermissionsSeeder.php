<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the Spatie roles and granular permissions.
 *
 * Safe to run repeatedly: findOrCreate/syncPermissions are idempotent and the
 * permission cache is refreshed at the end.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public const GUARD = 'web';

    /**
     * Every permission defined by the application.
     *
     * @var list<string>
     */
    public const ALL_PERMISSIONS = [
        'view categories',
        'create categories',
        'update categories',
        'delete categories',
        'view items',
        'create items',
        'update items',
        'delete items',
        'view batches',
        'create batches',
        'update batches',
        'delete batches',
        'view users',
        'create users',
        'update users',
        'delete users',
        'view reports',
        'view activity logs',
        'view notifications',
        'manage notifications',
    ];

    /**
     * Employee: existing category/item/batch CRUD, reports, activity viewing,
     * and access to their own notifications.
     *
     * @var list<string>
     */
    public const EMPLOYEE_PERMISSIONS = [
        'view categories',
        'create categories',
        'update categories',
        'delete categories',
        'view items',
        'create items',
        'update items',
        'delete items',
        'view batches',
        'create batches',
        'update batches',
        'delete batches',
        'view reports',
        'view activity logs',
        'view notifications',
        'manage notifications',
    ];

    /**
     * User: default role for public registration. Inventory/report viewing
     * and their own notifications only — no inventory mutations, no user
     * management.
     *
     * @var list<string>
     */
    public const USER_PERMISSIONS = [
        'view categories',
        'view items',
        'view batches',
        'view reports',
        'view notifications',
        'manage notifications',
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);

        // Seeding may run inside Model::withoutEvents(), which suppresses the
        // model events that normally flush Spatie's permission cache. Flush it
        // explicitly around the lookup phases so every step sees current rows.
        $registrar->forgetCachedPermissions();

        foreach (self::ALL_PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        $registrar->forgetCachedPermissions();

        // Role IDs 1 (Admin) and 2 (Employee) are preserved from the legacy
        // roles table; the User role is appended for public registration.
        $admin = Role::findOrCreate(RoleName::ADMIN->value, self::GUARD);
        $employee = Role::findOrCreate(RoleName::EMPLOYEE->value, self::GUARD);
        $user = Role::findOrCreate(RoleName::USER->value, self::GUARD);

        // Admin: all defined permissions, including user management.
        $admin->syncPermissions(self::ALL_PERMISSIONS);
        $employee->syncPermissions(self::EMPLOYEE_PERMISSIONS);
        $user->syncPermissions(self::USER_PERMISSIONS);

        // Repeated seeding stays safe and the permission cache is refreshed.
        $registrar->forgetCachedPermissions();
    }
}
