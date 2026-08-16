<?php

namespace App\Game\Automation\Exploration\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExplorationAttackMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User
     */
    public $user;

    /**
     * @var int
     */
    public $messages;

    /**
     * @param  User  $user  The user to broadcast attack messages to.
     * @param  array  $messages  The attack messages.
     */
    public function __construct(User $user, array $messages)
    {
        $this->user = $user;
        $this->messages = $messages;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('automation-attack-messages-'.$this->user->id);
    }
}
