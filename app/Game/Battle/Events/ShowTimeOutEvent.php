<?php

namespace App\Game\Battle\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\User;

class ShowTimeOutEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * User that sent the message
     *
     * @var \App\User
     */
    public $user;

    /**
     * show the bar
     *
     * @var bool $activateBar
     */
    public $activatebar;

    /**
     * can the player attack
     *
     * @var bool $canAttack
     */
    public $canAttack;

    public $forLength;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, bool $activatebar, bool $canAttack, int $forLength = 0)
    {
        $this->user        = $user;
        $this->activatebar = $activatebar;
        $this->canAttack   = $canAttack;
        $this->forLength   = $forLength;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('show-timeout-bar-' . $this->user->id);
    }
}
