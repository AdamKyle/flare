<?php

namespace App\Game\Character\CharacterInventory\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CharacterInventoryCountUpdateBroadcaseEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array
     */
    public $characterInventoryCount;

    /**
     * @var User
     */
    private $user;

    /**
     * Create a new event instance.
     */
    public function __construct(array $characterInventoryCount, User $user)
    {
        $this->user = $user;

        $this->characterInventoryCount = $characterInventoryCount;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-inventory-count-' . $this->user->id);
    }
}
