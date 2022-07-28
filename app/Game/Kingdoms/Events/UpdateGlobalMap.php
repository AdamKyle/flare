<?php

namespace App\Game\Kingdoms\Events;

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

class UpdateGlobalMap implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var array $otherKingdoms
     */
    public array $otherKingdoms;

    /**
     * @var array $npcKingdoms;
     */
    public array $npcKingdoms;

    /**
     * Create a new event instance.
     *
     * @param Character $character
     */
    public function __construct(Character $character) {
        $this->otherKingdoms = $this->getEnemyKingdoms($character, true);
        $this->npcKingdoms   = Kingdom::select('id', 'x_position', 'y_position', 'npc_owned', 'name')
                                      ->whereNull('character_id')
                                      ->where('game_map_id', $character->map->game_map_id)
                                      ->where('npc_owned', true)
                                      ->get()->toArray();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PresenceChannel('global-map-update');
    }
}
