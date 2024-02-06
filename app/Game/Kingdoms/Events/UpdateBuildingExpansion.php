<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\KingdomBuildingExpansion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\BuildingExpansionQueue;
use App\Flare\Models\User;
use App\Flare\Models\Character;
use App\Game\Core\Traits\KingdomCache;

class UpdateBuildingExpansion implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var User $users
     */
    private User $user;

    /**
     * @var KingdomBuildingExpansion $buildingExpansionQueue
     */
    public KingdomBuildingExpansion $kingdomBuildingExpansion;

    public int $timeLeft;

    /**
     * Create a new event instance.
     *
     * @param Character $character
     * @param KingdomBuildingExpansion $kingdomBuildingExpansion
     */
    public function __construct(Character $character, KingdomBuildingExpansion $kingdomBuildingExpansion) {
        $this->user                    = $character->user;
        $this->kingdomBuildingExpansion  = $kingdomBuildingExpansion;
        $this->timeLeft                = 0;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array {
        return new PrivateChannel('update-building-expansion-details-' . $this->user->id);
    }
}
