<?php

namespace App\Game\Factions\FactionLoyalty\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FactionLoyaltyUpdate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;

    public array $factionLoyalty;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, array $factionLoyalty)
    {
        $this->user = $user;
        $this->factionLoyalty = $factionLoyalty;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('faction-loyalty-update-'.$this->user->id);
    }
}
