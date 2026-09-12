<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['message_id', 'file_name', 'file_path', 'file_type', 'file_size'])]
class WorkspaceProjectMessageAttachment extends Model
{
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(WorkspaceProjectMessage::class, 'message_id');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->file_type, 'image/');
    }

    public function url(): string
    {
        return asset('storage/'.$this->file_path);
    }
}
