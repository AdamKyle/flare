<?php

namespace App\Game\Gambler\Events;

use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\Character;
use App\Game\Core\Traits\KingdomCache;

class GamblerSlotTimeOut implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var User $users
     */
    private User $user;

    /**
     * @var int $myKingdoms
     */
    public int $timeoutFor;


    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param int $timeOut
     */
    public function __construct(User $user, int $timeOut = 10) {
        $this->user        = $user;
        $this->timeoutFor  = $timeOut;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array {
        return new PrivateChannel('slot-timeout-' . $this->user->id);
    }
}
