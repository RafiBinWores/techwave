<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['admin_chat_message_id', 'file_name', 'file_path', 'file_type', 'file_size'])]
class AdminChatAttachment extends Model
{
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(AdminChatMessage::class, 'admin_chat_message_id');
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
