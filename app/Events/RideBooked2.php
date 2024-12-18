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

    public $apply;

    public function __construct(RideHistory $ride)
    {
        $this->apply = $apply;
    }

    public function broadcastOn()
    {
        new PrivateChannel('bookedrider.' . $this->ride->user_id);
        new PrivateChannel('bookeduser.' . $this->ride->user_id);
    }

    public function broadcastAs()
    {
        return 'RIDE_BOOKED';
    }

    public function broadcastWith()
    {
        return [
           'ride' => $this->ride
        ];
    }
}