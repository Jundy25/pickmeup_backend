<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RidesUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $rides;

    public function __construct($rides)
    {
        $this->rides = $rides;
    }

    public function broadcastOn()
    {
        return new Channel('rides');
    }

    public function broadcastAs()
    {
        return 'RIDES_UPDATE';
    }

    public function broadcastWith()
    {
        // Ensure the data is already transformed before broadcasting
        return [
            'rides' => $this->rides->toArray()
        ];
    }
}