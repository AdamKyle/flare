<?php

namespace App\Flare\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UpdateScheduledEvents implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $eventData;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $eventData) {
        $this->eventData = $eventData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PresenceChannel('update-event-schedule');
    }
}
