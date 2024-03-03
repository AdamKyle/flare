<?php

namespace App\Game\NpcActions\LabyrinthOracle\Events;

use App\Flare\Models\Character;
use App\Game\NpcActions\LabyrinthOracle\Services\ItemTransferService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\User;

class LabyrinthOracleUpdate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    private User $user;

    /**
     * @var array $inventory
     */
    public array $inventory;

    /**
     * Create a new event instance.
     *
     * @param Character $character
     * @return void
     */
    public function __construct(Character $character) {
        $this->user = $character->user;
        $this->inventory = resolve(ItemTransferService::class)->fetchInventoryItems($character);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-labyrinth-oracle-' . $this->user->id);
    }
}
