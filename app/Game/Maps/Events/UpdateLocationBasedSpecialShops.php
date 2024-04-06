<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use App\Game\Maps\Services\LocationService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;

class UpdateLocationBasedSpecialShops implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var User $user
     */
    private User $user;

    /**
     * @var bool $canAccessHellForgedShop
     */
    public bool $canAccessHellForgedShop     = false;

    /**
     * @var bool $canAccessPurgatoryChainsShop
     */
    public bool $canAccessPurgatoryChainsShop = false;

    /**
     * @var bool $camAccessTwistedEarthShop
     */
    public bool $camAccessTwistedEarthShop = false;

    /**
     * Create a new event instance.
     *
     * @param User $user
     */
    public function __construct(User $user) {
        $this->user = $user->refresh();;

        $this->canAccessHellForgedShop      = $this->canAccessHellForgedShop($user->character);
        $this->canAccessPurgatoryChainsShop = $this->canAccessPurgatoryChainsShop($user->character);
        $this->camAccessTwistedEarthShop    = $this->canAccessTwistedEarthShop($user->character);
    }

    /**
     * @param Character $character
     * @return bool
     */
    protected function canAccessHellForgedShop(Character $character): bool {

        return $character->map->gameMap->mapType()->isHell();
    }

    /**
     * @param Character $character
     * @return bool
     */
    protected function canAccessPurgatoryChainsShop(Character $character): bool {
        return $character->map->gameMap->mapType()->isPurgatory();
    }

    /**
     * @param Character $character
     * @return bool
     */
    protected function canAccessTwistedEarthShop(Character $character): bool {
        return $character->map->gameMap->mapType()->isTwistedMemories();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-location-base-shops-' . $this->user->id);
    }
}
