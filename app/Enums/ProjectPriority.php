<?php

namespace App\Enums;

enum ProjectPriority: string
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
}
