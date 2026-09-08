<?php

namespace App\Enums;

enum TaskStatus: string
{
    case BACKLOG = 'backlog';
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::BACKLOG => 'Backlog',
            self::TODO => 'Todo',
            self::IN_PROGRESS => 'In Progress',
            self::DONE => 'Done',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BACKLOG => 'slate',
            self::TODO => 'blue',
            self::IN_PROGRESS => 'amber',
            self::DONE => 'emerald',
            self::CANCELLED => 'red',
        };
    }
}
