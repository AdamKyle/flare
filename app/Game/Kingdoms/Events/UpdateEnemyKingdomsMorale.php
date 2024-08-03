<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateEnemyKingdomsMorale implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    /**
     * @var array
     */
    public $enemyMorale;

    /**
     * @var string
     */
    public $mapName;

    /**
     * Create a new event instance.
     *
     * @param  Character  $character
     */
    public function __construct(Kingdom $kingdom)
    {
        $this->mapName = $kingdom->gameMap->name;
        $this->enemyMorale = Kingdom::select('id', 'current_morale')
            ->where('game_map_id', $kingdom->game_map_id)
            ->where('id', $kingdom->id)
            ->first()
            ->toArray();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('enemy-kingdom-morale-update');
    }
}
