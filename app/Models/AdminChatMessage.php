<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sender_id', 'receiver_id', 'message', 'read_at'])]
class AdminChatMessage extends Model
{
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AdminChatAttachment::class);
    }

    public function scopeBetween(Builder $query, int $firstUserId, int $secondUserId): Builder
    {
        return $query->where(function (Builder $q) use ($firstUserId, $secondUserId) {
            $q->where('sender_id', $firstUserId)->where('receiver_id', $secondUserId);
        })->orWhere(function (Builder $q) use ($firstUserId, $secondUserId) {
            $q->where('sender_id', $secondUserId)->where('receiver_id', $firstUserId);
        });
    }

    public function previewText(): string
    {
        if ($this->message !== null && $this->message !== '') {
            return str($this->message)->limit(48);
        }

        return '(attachment)';
    }
}
