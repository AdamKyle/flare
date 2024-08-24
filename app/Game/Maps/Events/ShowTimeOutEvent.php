<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShowTimeOutEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User
     */
    private $user;

    /**
     * @var bool
     */
    public $activateBar;

    /**
     * @var bool
     */
    public $canMove;

    /**
     * @var int
     */
    public $forLength;

    /**
     * @var bool
     */
    public $setSail;

    /**
     * Create a new event instance.
     *
     * @param  int  $forLength  | 0
     * @param  bool  $setSail  | false
     * @return void
     */
    public function __construct(User $user, bool $activateBar, bool $canMove, int $forLength = 0, bool $setSail = false)
    {
        $this->user = $user;
        $this->activateBar = $activateBar;
        $this->canMove = $canMove;
        $this->forLength = $forLength;
        $this->setSail = $setSail;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('show-timeout-move-'.$this->user->id);
    }
}
