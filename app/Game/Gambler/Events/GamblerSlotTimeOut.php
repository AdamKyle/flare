<?php

namespace App\Game\Gambler\Events;

use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GamblerSlotTimeOut implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    private User $user;

    public int $timeoutFor;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, int $timeOut = 10)
    {
        $this->user = $user;
        $this->timeoutFor = $timeOut;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('slot-timeout-'.$this->user->id);
    }
}
