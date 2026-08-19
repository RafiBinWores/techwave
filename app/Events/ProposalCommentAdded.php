<?php

namespace App\Events;

use App\Models\ProposalComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProposalCommentAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public ProposalComment $comment) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.proposals'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'proposal.comment.added';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->comment->id,
            'proposal_id' => $this->comment->proposal_id,
            'proposal_no' => $this->comment->proposal?->proposal_no,
            'subject' => $this->comment->proposal?->subject,
            'author' => $this->comment->author,
            'body' => $this->comment->body,
            'created_at' => $this->comment->created_at?->diffForHumans(),
        ];
    }
}
