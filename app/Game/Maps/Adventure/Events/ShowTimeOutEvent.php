<?php

namespace App\Game\Maps\Adventure\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\User;

class ShowTimeOutEvent implements ShouldBroadcastNow
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
     * @var bool $canMove
     */
    public $canMove;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, bool $activatebar, bool $canMove)
    {
        $this->user        = $user;
        $this->activatebar = $activatebar;
        $this->canMove     = $canMove;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('show-timeout-move-' . $this->user->id);
    }
}
