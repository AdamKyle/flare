<?php

namespace App\Game\Battle\Events;

use App\Flare\ServerFight\MonsterPlayerFight;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateCelestialFight implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data = [];

    /**
     * Create a new event instance.
     */
    public function __construct(string $characterName, ?MonsterPlayerFight $celestialFight = null)
    {
        $this->data = [
            'monster_current_health' => is_null($celestialFight) ? 0 : $celestialFight->getMonsterHealth(),
            'celestial_fight_over' => is_null($celestialFight),
            'who_killed' => [
                'message' => 'Processing Fight',
                'type' => 'player-actions',
            ],
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PresenceChannel('celestial-fight-changes');
    }
}
