<?php

namespace App\Game\Exploration\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExplorationTimeOut implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User
     */
    private $user;

    /**
     * @var int
     */
    public $forLength;

    /**
     * @var bool
     */
    public $activateBar;

    /**
     * Create a new event instance.
     *
     * @param  int  $forLength  | 0
     * @return void
     */
    public function __construct(User $user, int $forLength = 0)
    {
        $this->user = $user;
        $this->forLength = $forLength;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('exploration-timeout-'.$this->user->id);
    }
}
