<?php

namespace App\Game\Events\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateEventGoalProgress implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $eventGoalData;

    /**
     * Create a new event instance.
     */
    public function __construct(array $eventGoalData)
    {
        $this->eventGoalData = $eventGoalData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     *
     * @codeCoverageIgnore
     */
    public function broadcastOn()
    {
        return new PresenceChannel('update-event-goal-progress');
    }
}
