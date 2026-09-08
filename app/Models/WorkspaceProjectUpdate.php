<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['workspace_project_id', 'user_id', 'parent_id', 'type', 'health', 'body', 'attachments'])]
class WorkspaceProjectUpdate extends Model
{
    protected function casts(): array
    {
        return [
            'attachments' => 'array',
        ];
    }

    public function project()
    {
        return $this->belongsTo(WorkspaceProject::class, 'workspace_project_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
