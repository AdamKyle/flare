<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Maps\Values\LocationBasedCraftingOptions;
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

    public bool $canAccessSeerCamp = false;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user->refresh();

        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($user->character);

        $this->canUseWorkBench = $locationBasedCraftingOptions->canUseWorkBench;
        $this->canUseQueenOfHearts = $locationBasedCraftingOptions->canUseQueenOfHearts;
        $this->canAccessLabyrinthOracle = $locationBasedCraftingOptions->canAccessLabyrinthOracle;
        $this->canAccessSeerCamp = $locationBasedCraftingOptions->canAccessSeerCamp;
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
