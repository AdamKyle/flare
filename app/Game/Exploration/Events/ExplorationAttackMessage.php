<?php

namespace App\Game\Exploration\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Flare\Models\User;

class ExplorationAttackMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    public $user;

    /**
     * @var int $forLength
     */
    public $messages;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param int $forLength | 0
     * @return void
     */
    public function __construct(User $user, array $messages)
    {
        $this->user        = $user;
        $this->messages    = $messages;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('exploration-attack-messages-' . $this->user->id);
    }
}
