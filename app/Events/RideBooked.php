<?php

namespace App\Events;

use App\Models\RideHistory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideBooked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ride;

    public function __construct(RideHistory $ride)
    {
        $this->ride = $ride;
    }

    public function broadcastOn()
    {
        return new Channel('rides'); // The channel to listen on
    }

    public function broadcastAs()
    {
        return 'RIDE_BOOKED'; // The event name
    }
}
