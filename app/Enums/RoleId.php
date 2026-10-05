<?php

namespace App\Enums;

enum RoleId: int
{
    case ADMIN = 1;
    case EMPLOYEE = 2;

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::EMPLOYEE => 'Employee',
        };
    }
}
