<?php

namespace App\Enums;

enum TaskActivityType: string
{
    case CREATED = 'created';
    case STATUS_CHANGED = 'status_changed';
    case PRIORITY_CHANGED = 'priority_changed';
    case ASSIGNEE_CHANGED = 'assignee_changed';
    case TITLE_CHANGED = 'title_changed';
    case DESCRIPTION_CHANGED = 'description_changed';
    case DUE_DATE_CHANGED = 'due_date_changed';
    case LABEL_ADDED = 'label_added';
    case LABEL_REMOVED = 'label_removed';
    case COMMENT_ADDED = 'comment_added';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Created',
            self::STATUS_CHANGED => 'Status changed',
            self::PRIORITY_CHANGED => 'Priority changed',
            self::ASSIGNEE_CHANGED => 'Assignee changed',
            self::TITLE_CHANGED => 'Title changed',
            self::DESCRIPTION_CHANGED => 'Description changed',
            self::DUE_DATE_CHANGED => 'Due date changed',
            self::LABEL_ADDED => 'Label added',
            self::LABEL_REMOVED => 'Label removed',
            self::COMMENT_ADDED => 'Comment added',
        };
    }
}
