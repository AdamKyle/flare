<?php

namespace App\Game\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Flare\Models\User;

class ShowTimeOutEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    private $user;

    /**
     * @var int $forLength
     */
    public $forLength;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param int $forLength | 0
     * @return void
     */
    public function __construct(User $user, int $forLength = 0)
    {
        $this->user        = $user;
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
