<?php

namespace App\Game\Events\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UpdateEventGoalProgress implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * @var array $eventGoalData
     */
    public array $eventGoalData;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param array $boons
     */
    public function __construct(array $eventGoalData) {
        $this->eventGoalData = $eventGoalData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     * @codeCoverageIgnore
     */
    public function broadcastOn() {
        return new PresenceChannel('update-event-goal-progress');
    }
}
