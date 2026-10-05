<?php

namespace App\Enums;

/**
 * The role names managed by spatie/laravel-permission.
 *
 * Role IDs are NOT part of the contract anymore: Spatie is the role authority
 * and roles are addressed by name (see RolesAndPermissionsSeeder).
 */
enum RoleName: string
{
    case ADMIN = 'Admin';

    case EMPLOYEE = 'Employee';

    case USER = 'User';
}
