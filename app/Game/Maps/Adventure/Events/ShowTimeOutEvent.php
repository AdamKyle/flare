<?php

namespace App\Game\Maps\Adventure\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\User;

class ShowTimeOutEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * User that sent the message
     *
     * @var \App\Flare\Models\User
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
     * how many seconds does the player have to wait?
     *
     * @var int $forLength
     */
    public $forLength;

    public $setSail;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, bool $activatebar, bool $canMove, int $forLength = 0, bool $setSail = false)
    {
        $this->user        = $user;
        $this->activatebar = $activatebar;
        $this->canMove     = $canMove;
        $this->forLength   = $forLength;
        $this->setSail     = $setSail;
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
