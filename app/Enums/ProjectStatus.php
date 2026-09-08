<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case BACKLOG = 'backlog';
    case PLANNING = 'planning';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::BACKLOG => 'Backlog',
            self::PLANNING => 'Planning',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BACKLOG => 'slate',
            self::PLANNING => 'blue',
            self::IN_PROGRESS => 'amber',
            self::COMPLETED => 'emerald',
            self::CANCELLED => 'red',
        };
    }
}
