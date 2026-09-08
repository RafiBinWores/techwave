<?php

namespace App\Enums;

enum ProjectMemberRole: string
{
    case MANAGER = 'manager';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::MANAGER => 'Project Manager',
            self::MEMBER => 'Member',
        };
    }
}
