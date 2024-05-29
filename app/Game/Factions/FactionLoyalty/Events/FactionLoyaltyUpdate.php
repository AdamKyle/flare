<?php

namespace App\Game\Factions\FactionLoyalty\Events;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\User;
use App\Flare\ServerFight\MonsterPlayerFight;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class FactionLoyaltyUpdate implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    private User $user;

    /**
     * @var array $factionLoyalty
     */
    public array $factionLoyalty;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param array $factionLoyalty
     */
    public function __construct(User $user, array $factionLoyalty) {
        $this->user = $user;
        $this->factionLoyalty = $factionLoyalty;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn() {
        return new PrivateChannel('faction-loyalty-update-' . $this->user->id);
    }
}
