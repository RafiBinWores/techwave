<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Events\ProjectMessageSent;
use App\Services\UserNotificationService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

#[Fillable(['workspace_project_id', 'sender_id', 'message', 'read_at'])]
class WorkspaceProjectMessage extends Model
{
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(WorkspaceProject::class, 'workspace_project_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(WorkspaceProjectMessageAttachment::class, 'message_id');
    }

    public function previewText(): string
    {
        if ($this->message !== null && $this->message !== '') {
            return str($this->message)->limit(48);
        }

        return '(attachment)';
    }

    public static function send(WorkspaceProject $project, string $body, array $attachments = []): self
    {
        $message = self::create([
            'workspace_project_id' => $project->getKey(),
            'sender_id' => Auth::id(),
            'message' => filled($body) ? trim($body) : null,
        ]);

        foreach ($attachments as $file) {
            $message->attachments()->create($file);
        }

        ProjectMessageSent::dispatch($message->fresh(['attachments']));

        self::notifyParticipants($project, $message);

        return $message;
    }

    protected static function notifyParticipants(WorkspaceProject $project, self $message): void
    {
        $senderId = (int) $message->sender_id;
        $recipientIds = collect();

        if ($project->client_id) {
            $recipientIds->push((int) $project->client_id);
        }

        $recipientIds = $recipientIds
            ->merge($project->members()->pluck('users.id'))
            ->push((int) $project->creator_id)
            ->filter()
            ->unique()
            ->reject(fn (int $id) => $id === $senderId)
            ->values();

        $sender = $message->sender;
        $preview = str($message->message ?: '(attachment)')->limit(60)->toString();

        $adminRoles = ['admin', 'admin_manager', 'manager', 'staff'];

        foreach ($recipientIds as $userId) {
            $user = User::query()->find($userId);
            $isAdmin = $user && in_array($user->role instanceof UserRole ? $user->role->value : $user->role, $adminRoles, true);

            $url = $isAdmin
                ? route('admin.workspace.projects.show.discussion', $project)
                : route('account.workspace-project.discussion', $project);

            UserNotificationService::notifyUser(
                $userId,
                ($sender?->name ?: 'Someone').' sent a message in '.$project->name,
                $preview,
                $sender?->name,
                $url
            );
        }
    }

    public static function markProjectAsRead(WorkspaceProject $project): void
    {
        self::query()
            ->where('workspace_project_id', $project->getKey())
            ->where('sender_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public static function unreadCount(WorkspaceProject $project, ?int $senderId = null): int
    {
        $query = self::query()
            ->where('workspace_project_id', $project->getKey())
            ->where('sender_id', '!=', Auth::id())
            ->whereNull('read_at');

        if ($senderId) {
            $query->where('sender_id', $senderId);
        }

        return $query->count();
    }
}
