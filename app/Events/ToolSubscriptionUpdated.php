<?php

namespace App\Events;

use App\Models\ToolSubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class ToolSubscriptionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public ToolSubscription $subscription, public string $actor = 'admin')
    {
        $this->subscription->loadMissing(['user', 'toolCategory', 'toolPlan']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.tool-subscriptions'),
            new PrivateChannel('user.'.$this->subscription->user_id.'.tool-subscriptions'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'tool-subscription.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->subscription->id,
            'status' => $this->subscription->status,
            'actor' => $this->actor,
            'user_id' => $this->subscription->user_id,
            'user_name' => $this->subscription->user?->name,
            'user_email' => $this->subscription->user?->email,
            'category' => $this->subscription->toolCategory?->name,
            'plan' => $this->subscription->toolPlan?->name,
            'amount' => (float) $this->subscription->amount,
            'billing_cycle' => $this->subscription->billing_cycle,
            'transaction_id' => $this->subscription->transaction_id,
            'updated_at' => $this->subscription->updated_at?->toDateTimeString(),
        ];
    }
}
