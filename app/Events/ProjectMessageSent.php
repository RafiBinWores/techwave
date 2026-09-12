<?php

namespace App\Events;

use App\Models\WorkspaceProjectMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public WorkspaceProjectMessage $message)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('project.'.$this->message->workspace_project_id.'.messages'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'project.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'project_id' => $this->message->workspace_project_id,
        ];
    }
}
 