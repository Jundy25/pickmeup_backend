<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RideApply implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $applicationData;

    public function __construct($applicationData)
    {
        $this->applicationData = $applicationData;
    }

    public function broadcastOn()
    {
        return new Channel('application');
    }

    public function broadcastAs()
    {
        return 'RIDES_APPLY';
    }

    public function broadcastWith()
    {
        return [
            'applicationData' => $this->applicationData
        ];
    }
}