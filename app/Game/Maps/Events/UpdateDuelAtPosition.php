<?php

namespace App\Game\Maps\Events;

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

        $data = Map::where('character_position_x', $user->character->map->character_position_x)
                               ->where('character_position_y', $user->character->map->character_position_y)
                               ->join('characters', function($join) {
                                   $join->on('characters.id', '=', 'maps.character_id');
                               })->select('characters.id as id', 'characters.name as name')->get();

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
