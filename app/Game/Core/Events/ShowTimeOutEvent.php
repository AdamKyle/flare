<?php

namespace App\Game\Core\Events;

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
     * @var int|float
     */
    public $forLength;

    /**
     * Create a new event instance.
     *
     * @param  int|float  $forLength  | 0
     * @return void
     */
    public function __construct(User $user, int|float $forLength = 0)
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
        return new PrivateChannel('show-timeout-bar-'.$this->user->id);
    }
}
