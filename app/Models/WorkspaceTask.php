<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'parent_task_id', 'identifier', 'title', 'description', 'attachments', 'status', 'priority', 'assignee_id', 'creator_id', 'due_date', 'estimated_hours', 'sort_order'])]
class WorkspaceTask extends Model
{
    // protected $table = 'workspace_tasks';

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
            'estimated_hours' => 'integer',
            'sort_order' => 'integer',
            'attachments' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(WorkspaceProject::class, 'project_id');
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_task_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(WorkspaceLabel::class, 'workspace_label_task', 'task_id', 'label_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(WorkspaceTaskComment::class, 'task_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(WorkspaceTaskActivity::class, 'task_id');
    }

    public function isSubtask(): bool
    {
        return $this->parent_task_id !== null;
    }

    public function subtaskCount(): int
    {
        return $this->subtasks()->count();
    }

    public function completedSubtaskCount(): int
    {
        return $this->subtasks()->where('status', TaskStatus::DONE)->count();
    }
}
