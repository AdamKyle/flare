<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\Character;
use App\Flare\Models\Map;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Game\Core\Traits\KingdomCache;
Use App\Flare\Models\User;

class UpdateDuelAtPosition implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var array $character
     */
    public $characters;

    /**
     * @var User $user
     */
    private $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     */
    public function __construct(User $user) {
        $character       = $user->refresh()->character;

        if ($character->level < 301) {
            $data = [];
        } else {
            $data = Character::where('level', '>=', 301)
                             ->where('killed_in_pvp', false)
                             ->join('maps', function($join) use($character) {
                                 $join->on('maps.character_id', '=', 'characters.id')
                                      ->where('maps.game_map_id', '=', $character->map->game_map_id)
                                      ->where('maps.character_position_x', $character->map->character_position_x)
                                      ->where('maps.character_position_y', $character->map->character_position_y);
                            })->select('characters.id as id',
                                       'characters.name as name',
                                       'maps.character_position_x',
                                       'maps.character_position_y',
                                       'maps.game_map_id as game_map_id',
                            )->get();
        }

        $this->characters = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('update-duel');
    }
}
