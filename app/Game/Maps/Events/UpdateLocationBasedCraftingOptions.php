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

class UpdateLocationBasedCraftingOptions implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;


    /**
     * @var User $user
     */
    private $user;

    public $canUseWorkBench = false;

    public $canUseQueenOfHearts = false;

    /**
     * Create a new event instance.
     *
     * @param User $user
     */
    public function __construct(User $user) {
        $user = $user->refresh();

        $this->user = $user;

        $this->canUseWorkBench     = $this->canUseWorkBench($user->character);
        $this->canUseQueenOfHearts = $this->canUseQueenOfHearts($user->character);
    }

    /**
     * @param Character $character
     * @return bool
     */
    protected function canUseWorkBench(Character $character): bool {

        if (!$character->map->gameMap->mapType()->isPurgatory()) {
            return false;
        }

        return true;
    }

    /**
     * @param Character $character
     * @return bool
     */
    protected function canUseQueenOfHearts(Character $character): bool {
        return $character->inventory->slots->filter(function($slot) {
            return $slot->item->effect === ItemEffectsValue::QUEEN_OF_HEARTS;
        })->isNotEmpty() && $character->map->gameMap->mapType()->isHell();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-location-base-crafting-options-' . $this->user->id);
    }
}
