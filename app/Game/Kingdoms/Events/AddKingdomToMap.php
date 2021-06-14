<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\Kingdom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\Character;
use App\Game\Core\Traits\KingdomCache;

class AddKingdomToMap implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var User $users
     */
    public $user;

    /**
     * @var array $kingdoms
     */
    public $kingdoms;

    /**
     * @var array $npcKingdoms
     */
    public $npcKingdoms;

    /**
     * Create a new event instance.
     *
     * @param Character $character
     */
    public function __construct(Character $character)
    {
        $this->user        = $character->user;
        $this->kingdoms    = $this->getKingdoms($character);
        $this->npcKingdoms = Kingdom::select('x_position', 'y_position', 'id')
                                ->whereNull('character_id')
                                ->where('game_map_id', $character->map->game_map_id)
                                ->get()
                                ->toArray();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('add-kingdom-to-map-' . $this->user->id);
    }
}
