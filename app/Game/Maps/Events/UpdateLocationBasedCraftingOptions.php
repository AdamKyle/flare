<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateLocationBasedCraftingOptions implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    private User $user;

    public bool $canUseWorkBench = false;

    public bool $canUseQueenOfHearts = false;

    public bool $canAccessLabyrinthOracle = false;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user->refresh();

        $this->canUseWorkBench = $this->canUseWorkBench($user->character);
        $this->canUseQueenOfHearts = $this->canUseQueenOfHearts($user->character);
        $this->canAccessLabyrinthOracle = $this->canUseLabyrinthOracle($user->character);
    }

    protected function canUseWorkBench(Character $character): bool
    {
        return $character->map->gameMap->mapType()->isPurgatory();
    }

    protected function canUseQueenOfHearts(Character $character): bool
    {
        return $character->inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectsValue::QUEEN_OF_HEARTS;
        })->isNotEmpty() && $character->map->gameMap->mapType()->isHell();
    }

    protected function canUseLabyrinthOracle(Character $character): bool
    {
        return $character->map->gameMap->mapType()->isLabyrinth();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-location-base-crafting-options-'.$this->user->id);
    }
}
