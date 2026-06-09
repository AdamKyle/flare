<?php

namespace App\Game\Shop\Events;

use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateShopEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    private User $user;

    public int $gold;

    public int $inventoryCount;

    public function __construct(User $user, int $gold, int $inventoryCount)
    {
        $this->user = $user;
        $this->gold = $gold;
        $this->inventoryCount = $inventoryCount;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('update-shop-'.$this->user->id);
    }
}
