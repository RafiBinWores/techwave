<?php

namespace App\Models;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description', 'details', 'status', 'priority', 'icon', 'icon_color', 'image_path', 'start_date', 'target_date', 'creator_id', 'client_id', 'is_active'])]
class WorkspaceProject extends Model
{
    protected $table = 'workspace_projects';

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'priority' => ProjectPriority::class,
            'start_date' => 'date',
            'target_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function (WorkspaceProject $project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_project_members', 'project_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WorkspaceTask::class, 'project_id');
    }

    public function labels(): HasMany
    {
        return $this->hasMany(WorkspaceLabel::class, 'project_id');
    }

    protected function label(): Attribute
    {
        return Attribute::get(fn () => $this->labels()->value('name'));
    }

    public function memberCount(): int
    {
        return $this->members()->count();
    }

    public function taskCount(?string $status = null): int
    {
        $query = $this->tasks();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }

    public function ensureLabel(string $labelName, ?string $color = null): ?WorkspaceLabel
    {
        $labelName = trim($labelName);

        if ($labelName === '') {
            return null;
        }

        if ($color === null) {
            $color = WorkspaceLabel::query()->where('name', $labelName)->value('color');
        }

        $color ??= 'slate';

        return $this->labels()->firstOrCreate(
            ['name' => $labelName],
            ['color' => $color]
        );
    }

    public function updates()
    {
        return $this->hasMany(WorkspaceProjectUpdate::class)
            ->latest();
    }
}
