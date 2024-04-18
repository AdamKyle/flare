<?php

namespace App\Game\Events\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UpdateEventGoalCurrentProgressForCharacter implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;


    private int $userId;
    public string $amount;

    /**
     * Create a new event instance.
     *
     * @param int $userId
     * @param int $amount
     */
    public function __construct(int $userId, int $amount) {
        $this->userId = $userId;
        $this->amount = $amount;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     * @codeCoverageIgnore
     */
    public function broadcastOn() {
        return new PrivateChannel('player-current-event-goal-progression-' . $this->userId);
    }
}
