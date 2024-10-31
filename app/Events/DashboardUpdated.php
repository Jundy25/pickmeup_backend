<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $counts;
    public $bookings;

    public function __construct($counts, $bookings)
    {
        $this->counts = $counts;
        $this->bookings = $bookings;
    }

    public function broadcastOn()
    {
        return new Channel('dashboard');
    }
}
