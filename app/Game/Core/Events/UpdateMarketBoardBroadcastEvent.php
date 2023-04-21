<?php

namespace App\Game\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;

class UpdateMarketBoardBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param array $marketListings
     */
    public $marketListings;

    /**
     * @param int $characterGold 
     */
    public $characterGold;

    /**
     * @param User $user
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param array $marketListings
     * @param int $characterGold
     * @return void
     */
    public function __construct(User $user, array $marketListings, int $characterGold)
    {
        $this->marketListings = $marketListings;
        $this->characterGold  = $characterGold;
        $this->user           = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('update-market');
    }
}
