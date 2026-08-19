<?php

namespace App\Events;

use App\Models\Proposal;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProposalStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Proposal $proposal) {}

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
        return 'proposal.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'proposal_id' => $this->proposal->id,
            'proposal_no' => $this->proposal->proposal_no,
            'subject' => $this->proposal->subject,
            'status' => $this->proposal->status,
        ];
    }
}
