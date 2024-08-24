<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\Character;
use App\Flare\Models\KingdomBuildingExpansion;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateBuildingExpansion implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    private User $user;

    public KingdomBuildingExpansion $kingdomBuildingExpansion;

    public int $timeLeft;

    /**
     * Create a new event instance.
     */
    public function __construct(Character $character, KingdomBuildingExpansion $kingdomBuildingExpansion)
    {
        $this->user = $character->user;
        $this->kingdomBuildingExpansion = $kingdomBuildingExpansion;
        $this->timeLeft = 0;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('update-building-expansion-details-'.$this->user->id);
    }
}
