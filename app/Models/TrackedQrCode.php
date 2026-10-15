<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['token', 'user_id', 'content_type', 'content', 'svg'])]
class TrackedQrCode extends Model
{
    protected function casts(): array
    {
        return ['content' => 'encrypted', 'svg' => 'encrypted'];
    }

    public function dailyScans(): HasMany
    {
        return $this->hasMany(QrDailyScan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
