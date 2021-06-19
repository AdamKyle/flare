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

class UpdateEnemyKingdomsMorale implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var array $enemyMorale
     */
    public $enemyMorale;

    /**
     * @var string $mapName
     */
    public $mapName;

    /**
     * Create a new event instance.
     *
     * @param Character $character
     */
    public function __construct(Kingdom $kingdom) {
        $this->mapName     = $kingdom->gameMap->name;
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
