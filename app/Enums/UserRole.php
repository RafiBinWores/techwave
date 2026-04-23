<?php

namespace App\Enums;

enum UserRole: string
{
    case CLIENT = 'client';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case STAFF = 'staff';
    case ADMIN_MANAGER = 'admin_manager';

    public function label(): string
    {
        return match ($this) {
            self::CLIENT => 'Client',
            self::ADMIN => 'Admin',
            self::MANAGER => 'Manager',
            self::STAFF => 'Staff',
            self::ADMIN_MANAGER => 'Admin Manager',
        };
    }
}
