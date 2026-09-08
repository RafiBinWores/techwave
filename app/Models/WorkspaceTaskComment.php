<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_id', 'user_id', 'body', 'attachments'])]
class WorkspaceTaskComment extends Model
{
    protected $table = 'workspace_task_comments';

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
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
