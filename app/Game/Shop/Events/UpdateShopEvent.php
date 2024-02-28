<?php

namespace App\Game\Shop\Events;

use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\Character;
use App\Game\Core\Traits\KingdomCache;

class UpdateShopEvent implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var User $users
     */
    private User $user;

    /**
     * @var int $gold
     */
    public int $gold;

    /**
     * @var int $inventoryCount
     */
    public int $inventoryCount;


    /**
     * @param User $user
     * @param int $gold
     * @param int $inventoryCount
     */
    public function __construct(User $user, int $gold, int $inventoryCount) {
        $this->user           = $user;
        $this->gold           = $gold;
        $this->inventoryCount = $inventoryCount;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array {
        return new PrivateChannel('update-shop-' . $this->user->id);
    }
}
