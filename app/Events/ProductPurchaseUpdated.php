<?php

namespace App\Events;

use App\Models\ProductPurchase;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductPurchaseUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Queue the broadcast only after the purchase transaction commits.
    public bool $afterCommit = true;

    private array $payload;

    /**
     * Create a new event instance.
     */
    public function __construct(ProductPurchase $purchase)
    {
        $this->payload = [
            'id' => $purchase->id,
            'status' => $purchase->status,
            'product' => $purchase->product_name,
            'plan' => $purchase->plan_name,
            'billing_cycle' => $purchase->billing_cycle,
            'amount' => $purchase->amount,
            'renewal' => $purchase->renewal_of_id !== null,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.product-purchases')];
    }

    public function broadcastAs(): string
    {
        return 'product-purchase.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
