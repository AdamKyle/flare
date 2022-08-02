<?php

namespace App\Game\Maps\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\User;

class ShowTimeOutEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User
     */
    private $user;

    /**
     * @var bool $activateBar
     */
    public $activateBar;

    /**
     * @var bool $canMove
     */
    public $canMove;

    /**
     * @var int $forLength
     */
    public $forLength;

    /**
     * @var bool $setSail
     */
    public $setSail;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param bool $activateBar
     * @param bool $canMove
     * @param int $forLength | 0
     * @param bool $setSail | false
     * @return void
     */
    public function __construct(User $user, bool $activateBar, bool $canMove, int $forLength = 0, bool $setSail = false)
    {
        $this->user        = $user;
        $this->activateBar = $activateBar;
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
