<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\RideHistory;

class RidesBooked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ride;

    public function __construct(RideHistory $ride)
    {
        $this->ride = $ride;
    }

    public function broadcastOn()
    {
        // Broadcasting to both user and rider on their private channels
        return [
            new PrivateChannel('user.' . $this->ride->user_id),
            new PrivateChannel('user.' . $this->ride->rider_id)
        ];
    }

    public function broadcastAs()
    {
        return 'ride.booked';
    }

    public function broadcastWith()
    {
        return [
            'ride_id' => $this->ride->ride_id,
            'status' => $this->ride->status,
            'pickup_location' => $this->ride->pickup_location,
            'dropoff_location' => $this->ride->dropoff_location,
            'ride_date' => $this->ride->ride_date,
            'fare' => $this->ride->fare,
            // Add any other relevant ride details you want to broadcast
        ];
    }
}