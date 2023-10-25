<?php

namespace App\Game\Skills\Events;

use App\Game\CharacterInventory\Services\CharacterInventoryService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\User;

class UpdateCharacterAlchemyList implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * @var array $items
     */
    public $items;

    /**
     * @var User $users
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param array $items
     */
    public function __construct(User $user, Collection $items) {
        $this->user      = $user;
        $this->items     = $items;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-alchemy-list-' . $this->user->id);
    }
}
