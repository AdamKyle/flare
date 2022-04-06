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

class ShowCraftingTimeOutEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    private $user;

    /**
     * @var int $timeout
     */
    public $timeout;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param int|null $timeout
     */
    public function __construct(User $user, int $timeout = null) {
        $this->user    = $user;
        $this->timeout = $timeout;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('show-crafting-timeout-bar-' . $this->user->id);
    }
}
