<?php

namespace App\Game\Character\CharacterInventory\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CharacterInventoryDetailsUpdate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * @var array $inventory
     */
    public $inventoryDetails;

    /**
     * @var User $users
     */
    private $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param array $boons
     */
    public function __construct(User $user) {
        $this->user             = $user;
        $this->inventoryDetails = $this->fetchDetails($user);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-inventory-details-' . $this->user->id);
    }

    /**
     * @param User $user
     * @return array
     */
    protected function fetchDetails(User $user): array {
        $character = $user->refresh()->character;

        return [
            'gold'           => number_format($character->gold),
            'gold_dust'      => number_format($character->gold_dust),
            'shards'         => number_format($character->shards),
            'copper_coins'   => number_format($character->copper_coins),
            'inventory_used' => $character->getInventoryCount(),
            'inventory_max'  => $character->inventory_max,
            'damage_stat'    => $character->damage_stat,
            'to_hit_stat'    => $character->class->to_hit_stat,
        ];
    }
}
