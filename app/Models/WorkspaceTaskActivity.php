<?php

namespace App\Models;

use App\Enums\TaskActivityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_id', 'user_id', 'type', 'old_value', 'new_value'])]
class WorkspaceTaskActivity extends Model
{
    protected $table = 'workspace_task_activities';

    protected function casts(): array
    {
        return [
            'type' => TaskActivityType::class,
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(WorkspaceTask::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
