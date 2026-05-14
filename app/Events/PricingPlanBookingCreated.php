<?php

namespace App\Events;

use App\Models\PricingPlanBooking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PricingPlanBookingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public PricingPlanBooking $booking)
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
            new PrivateChannel('admin.pricing-bookings'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pricing.booking.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->booking->id,
            'booking_no' => $this->booking->booking_no,
            'customer_name' => $this->booking->customer_name,
            'company_name' => $this->booking->company_name,
            'plan_price' => $this->booking->plan_price,
            'requested_price' => $this->booking->requested_price,
            'status' => $this->booking->status,
        ];
    }
}
