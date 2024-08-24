<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateLocationBasedSpecialShops implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    private User $user;

    public bool $canAccessHellForgedShop = false;

    public bool $canAccessPurgatoryChainsShop = false;

    public bool $camAccessTwistedEarthShop = false;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user->refresh();

        $this->canAccessHellForgedShop = $this->canAccessHellForgedShop($user->character);
        $this->canAccessPurgatoryChainsShop = $this->canAccessPurgatoryChainsShop($user->character);
        $this->camAccessTwistedEarthShop = $this->canAccessTwistedEarthShop($user->character);
    }

    protected function canAccessHellForgedShop(Character $character): bool
    {

        return $character->map->gameMap->mapType()->isHell();
    }

    protected function canAccessPurgatoryChainsShop(Character $character): bool
    {
        return $character->map->gameMap->mapType()->isPurgatory();
    }

    protected function canAccessTwistedEarthShop(Character $character): bool
    {
        return $character->map->gameMap->mapType()->isTwistedMemories();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-location-base-shops-'.$this->user->id);
    }
}
