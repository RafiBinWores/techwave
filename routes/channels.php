<?php

use App\Enums\UserRole;
use App\Models\SupportTicket;
use App\Models\WorkspaceProject;
use Illuminate\Support\Facades\Broadcast;

$adminRoles = [
    UserRole::ADMIN,
    UserRole::MANAGER,
    UserRole::STAFF,
    UserRole::ADMIN_MANAGER,
];

Broadcast::channel('admin.tickets', function ($user) use ($adminRoles) {
    return $user && in_array($user->role, $adminRoles);
});

Broadcast::channel('ticket.{ticketId}', function ($user, $ticketId) use ($adminRoles) {
    if (! $user) {
        return false;
    }

    if (in_array($user->role, $adminRoles)) {
        return true;
    }

    return SupportTicket::query()
        ->where('id', $ticketId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('user.{userId}.tickets', function ($user, $userId) use ($adminRoles) {
    if (! $user) {
        return false;
    }

    if (in_array($user->role, $adminRoles)) {
        return true;
    }

    return (int) $user->id === (int) $userId;
});

Broadcast::channel('admin.proposals', function ($user) use ($adminRoles) {
    return $user && in_array($user->role ?? null, $adminRoles, true);
});

Broadcast::channel('admin.contact-messages', function ($user) use ($adminRoles) {
    return $user && in_array($user->role ?? null, $adminRoles, true);
});

Broadcast::channel('admin.bookings', function ($user) use ($adminRoles) {
    return $user && in_array($user->role ?? null, $adminRoles, true);
});

Broadcast::channel('user.{userId}.chat', function ($user, $userId) use ($adminRoles) {
    return $user
        && in_array($user->role ?? null, $adminRoles, true)
        && (int) $user->id === (int) $userId;
});


Broadcast::channel('user.{userId}.notifications', function ($user, $userId) {
    return $user && (int) $user->id === (int) $userId;
});

Broadcast::channel('project.{projectId}.messages', function ($user, $projectId) {
    if (! $user) {
        return false;
    }

    $project = WorkspaceProject::query()->find($projectId);

    if (! $project) {
        return false;
    }

    if (in_array($user->role, ['admin', 'admin_manager'], true)) {
        return true;
    }

    if ((int) $project->client_id === (int) $user->id) {
        return true;
    }

    if ((int) $project->creator_id === (int) $user->id) {
        return true;
    }

    return $project->members()->where('users.id', $user->id)->exists();
});
