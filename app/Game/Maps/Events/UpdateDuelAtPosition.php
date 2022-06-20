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
        $this->user      = $user->refresh();

        $data = Map::where('game_map_id', $user->character->map->game_map_id)
                   ->join('characters', function($join) {
                       $join->on('characters.id', '=', 'maps.character_id')
                            ->where('characters.killed_in_pvp', '=', false);
                   })->select('characters.id as id', 'characters.name as name', 'maps.character_position_x', 'maps.character_position_y', 'maps.game_map_id as game_map_id')
                     ->get();

        $data = $data->filter(function($character) {
            $characterModel = Character::find($character->id);

            if ($characterModel->currentAutomations->isEmpty() && $characterModel->level >= 301) {
                return $character;
            }
        });

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
