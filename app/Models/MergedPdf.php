<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'session_id',
    'output_name',
    'output_path',
    'output_size',
    'source_names',
    'source_paths',
    'is_backup_enabled',
    'status',
    'error_message',
    'job_id',
    'processed_at',
    'expires_at',
    'backup_expires_at',
])]
class MergedPdf extends Model
{
    protected function casts(): array
    {
        return [
            'output_size' => 'integer',
            'source_names' => 'array',
            'source_paths' => 'array',
            'is_backup_enabled' => 'boolean',
            'processed_at' => 'datetime',
            'expires_at' => 'datetime',
            'backup_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return in_array(
            $this->status,
            ['pending', 'processing'],
            true
        );
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function belongsToCurrentVisitor(): bool
    {
        if ($this->user_id !== null) {
            return auth()->check()
                && auth()->id() === $this->user_id;
        }

        if ($this->session_id === null) {
            return false;
        }

        return hash_equals(
            (string) $this->session_id,
            (string) session()->getId()
        );
    }

    public function outputFileExists(): bool
    {
        if (empty($this->output_path)) {
            return false;
        }

        return Storage::disk(
            config('pdf-compressor.storage_disk')
        )->exists($this->output_path);
    }

    public function deleteOutputFile(): bool
    {
        if ($this->outputFileExists()) {
            return Storage::disk(
                config('pdf-compressor.storage_disk')
            )->delete($this->output_path);
        }

        return true;
    }

    public function sourceCount(): int
    {
        return count($this->source_names ?? []);
    }

    /**
     * @return array<int, string>
     */
    public function sourceFilePaths(): array
    {
        $paths = $this->source_paths ?? [];

        return array_values(array_filter(
            $paths,
            fn ($path) => is_string($path) && $path !== ''
        ));
    }
}
