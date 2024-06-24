<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\Character;
use App\Game\Core\Traits\KingdomCache;

class UpdateBuildingUpgrades implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var User $users
     */
    private User $user;

    /**
     * @var array $myKingdoms
     */
    public array $kingdomBuildingData;

    /**
     * Create a new event instance.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     */
    public function __construct(Character $character, Kingdom $kingdom) {

        $kingdomBuildingData = resolve(CapitalCityManagementService::class)
                ->fetchBuildingsForUpgradesOrRepairs($character, $kingdom, true);

        $this->user                 = $character->user;
        $this->kingdomBuildingData  = $kingdomBuildingData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array {
        return new PrivateChannel('update-kingdom-building-data-' . $this->user->id);
    }
}
