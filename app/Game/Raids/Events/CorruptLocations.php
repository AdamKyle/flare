<?php

namespace App\Game\Raids\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class CorruptLocations implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $corruptedLocations;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $eventData) {
        $this->corruptedLocations = $eventData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PresenceChannel('corrupt-locations');
    }
}
