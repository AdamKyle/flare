<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class UpdateNPCKingdoms implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    /**
     * @var string
     */
    public $mapName;

    /**
     * @var Collection
     */
    public $npcKingdoms;

    /**
     * Create a new event instance.
     *
     * @param  Character  $character
     */
    public function __construct(GameMap $map)
    {
        $this->mapName = $map->name;
        $this->npcKingdoms = Kingdom::select('id', 'x_position', 'y_position', 'npc_owned', 'name')
            ->where('character_id', null)
            ->where('npc_owned', true)
            ->where('game_map_id', $map->id)->get();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('npc-kingdoms-update');
    }
}
