<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateDuelAtPosition implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    /**
     * @var array
     */
    public $characters;

    /**
     * @var User
     */
    private $user;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $character = $user->refresh()->character;

        if ($character->level < 301) {
            $data = [];
        } else {
            $data = Character::where('level', '>=', 301)
                ->where('killed_in_pvp', false)
                ->join('maps', function ($join) use ($character) {
                    $join->on('maps.character_id', '=', 'characters.id')
                        ->where('maps.game_map_id', '=', $character->map->game_map_id);
                })->whereNotIn('characters.id', function ($query) {
                    $query->select('character_id')
                        ->from('character_automations');
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
