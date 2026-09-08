<?php

namespace App\Enums;

enum TaskPriority: string
{
    case URGENT = 'urgent';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case NONE = 'none';

    public function label(): string
    {
        return match ($this) {
            self::URGENT => 'Urgent',
            self::HIGH => 'High',
            self::MEDIUM => 'Medium',
            self::LOW => 'Low',
            self::NONE => 'No Priority',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::URGENT => 'flag',
            self::HIGH => 'flag',
            self::MEDIUM => 'flag',
            self::LOW => 'flag',
            self::NONE => 'outlined_flag',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::URGENT => 'red',
            self::HIGH => 'orange',
            self::MEDIUM => 'yellow',
            self::LOW => 'blue',
            self::NONE => 'slate',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::URGENT => 0,
            self::HIGH => 1,
            self::MEDIUM => 2,
            self::LOW => 3,
            self::NONE => 4,
        };
    }
}
