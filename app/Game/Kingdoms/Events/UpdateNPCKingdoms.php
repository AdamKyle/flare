<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\Character;
use App\Game\Core\Traits\KingdomCache;
use Illuminate\Support\Collection;

class UpdateNPCKingdoms implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var string $mapName
     */
    public $mapName;

    /**
     * @var Collection $npcKingdoms
     */
    public $npcKingdoms;

    /**
     * Create a new event instance.
     *
     * @param Character $character
     */
    public function __construct(GameMap $map) {
        $this->mapName       = $map->name;
        $this->npcKingdoms   = Kingdom::select('x_position', 'y_position', 'npc_owned')->where('character_id', null)->where('npc_owned', true)->where('game_map_id', $map->id)->get();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PresenceChannel('npc-kingdoms-update');
    }
}
