<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'session_id',
    'original_name',
    'original_path',
    'original_size',
    'output_path',
    'output_size',
    'page_count',
    'mode',
    'range_start',
    'range_end',
    'ranges',
    'combine_ranges',
    'selected_pages',
    'is_backup_enabled',
    'status',
    'error_message',
    'job_id',
    'processed_at',
    'expires_at',
    'backup_expires_at',
])]
class SplitPdf extends Model
{
    protected function casts(): array
    {
        return [
            'original_size' => 'integer',
            'output_size' => 'integer',
            'page_count' => 'integer',
            'range_start' => 'integer',
            'range_end' => 'integer',
            'ranges' => 'array',
            'combine_ranges' => 'boolean',
            'selected_pages' => 'array',
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

    public function originalFileExists(): bool
    {
        if (empty($this->original_path)) {
            return false;
        }

        return Storage::disk(
            config('pdf-compressor.storage_disk')
        )->exists($this->original_path);
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

    public function deleteOriginalFile(): bool
    {
        if ($this->originalFileExists()) {
            return Storage::disk(
                config('pdf-compressor.storage_disk')
            )->delete($this->original_path);
        }

        return true;
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

    public function outputIsZip(): bool
    {
        return str_ends_with(
            strtolower((string) $this->output_path),
            '.zip'
        );
    }

    public function outputDownloadName(): string
    {
        $base = pathinfo(
            $this->original_name,
            PATHINFO_FILENAME
        );

        if ($this->outputIsZip()) {
            return $base.'_split.zip';
        }

        if ($this->mode === 'range') {
            $ranges = (array) $this->ranges;

            if (count($ranges) > 1) {
                $labels = [];

                foreach ($ranges as $range) {
                    $labels[] = ($range['start'] ?? 0).'-'.($range['end'] ?? 0);
                }

                return $base.'_pages-'.implode('_', $labels).'.pdf';
            }

            return $base.'_pages-'.$this->range_start.'-'.$this->range_end.'.pdf';
        }

        if ($this->mode === 'custom') {
            return $base.'_selected-pages.pdf';
        }

        return $base.'_split.pdf';
    }
}
