<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateGlobalCharacterCountBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var int
     */
    public $characterCount = 0;

    /**
     * @var string
     */
    public $mapName;

    /**
     * Create a new event instance.
     *
     * @param  int  $mapId
     */
    public function __construct(GameMap $gameMap)
    {
        $this->characterCount = $this->getCharacterCount($gameMap);

        $this->mapName = $gameMap->name;
    }

    protected function getCharacterCount(GameMap $gameMap)
    {
        return Character::join('maps', function ($query) use ($gameMap) {
            $query->on('characters.id', 'maps.character_id')->where('game_map_id', $gameMap->id);
        })->join('sessions', function ($join) {
            $join->on('sessions.user_id', 'characters.user_id')
                ->where('last_activity', '<', now()->addHours()->timestamp);
        })->count();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('global-character-count-plane');
    }
}
