<?php

namespace App\Services;

use App\Events\UserNotificationCreated;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WorkspaceProject;
use Illuminate\Support\Collection;

class UserNotificationService
{
    public static function notifyUser(int $userId, string $title, ?string $subject = null, ?string $from = null, ?string $url = null, string $type = 'workspace'): UserNotification
    {
        $notification = UserNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'subject' => $subject,
            'from' => $from,
            'url' => $url,
        ]);

        UserNotificationCreated::dispatch($notification);

        return $notification;
    }

    /**
     * @param  Collection<int, int>|array<int, int>  $userIds
     */
    public static function notifyUsers(Collection|array $userIds, string $title, ?string $subject = null, ?string $from = null, ?string $url = null, string $type = 'workspace'): Collection
    {
        $notifications = collect();

        foreach (array_unique(collect($userIds)->filter()->values()->all()) as $userId) {
            $notifications->push(self::notifyUser($userId, $title, $subject, $from, $url, $type));
        }

        return $notifications;
    }

    public static function notifyProjectMembers(WorkspaceProject $project, string $title, ?string $subject = null, ?string $from = null, ?string $url = null, string $type = 'workspace', bool $includeCreator = false): Collection
    {
        $memberIds = $project->members()->pluck('users.id');

        if ($includeCreator && $project->creator_id) {
            $memberIds = $memberIds->push($project->creator_id);
        }

        return self::notifyUsers($memberIds, $title, $subject, $from, $url, $type);
    }

    public static function authorName(User $user): string
    {
        return $user->name ?? 'A user';
    }
}
