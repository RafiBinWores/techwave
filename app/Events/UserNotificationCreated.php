<?php

namespace App\Events;

use App\Models\UserNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $notificationId;

    public int $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(UserNotification $notification)
    {
        $this->notificationId = $notification->id;
        $this->userId = $notification->user_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->userId.'.notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.notification';
    }

    public function broadcastWith(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'user_id' => $this->userId,
        ];
    }
}
