<?php

namespace App\Game\NpcActions\LabyrinthOracle\Events;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use App\Game\NpcActions\LabyrinthOracle\Services\ItemTransferService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LabyrinthOracleUpdate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;

    public array $inventory;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Character $character)
    {
        $this->user = $character->user;
        $this->inventory = resolve(ItemTransferService::class)->fetchInventoryItems($character);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-labyrinth-oracle-'.$this->user->id);
    }
}
