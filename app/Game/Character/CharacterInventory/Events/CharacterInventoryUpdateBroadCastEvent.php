<?php

namespace App\Game\Character\CharacterInventory\Events;

use App\Flare\Models\User;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CharacterInventoryUpdateBroadCastEvent implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * @var array $inventory
     */
    public array $inventory;

    /**
     * @var string $type
     */
    public string $type;

    /**
     * @var User $users
     */
    private $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param string $type
     */
    public function __construct(User $user, string $type) {
        $this->user      = $user;
        $this->inventory = resolve(CharacterInventoryService::class)
            ->setCharacter($user->character->refresh())
            ->getInventoryForType($type);
        $this->type      = $type;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-inventory-' . $this->user->id);
    }
}
