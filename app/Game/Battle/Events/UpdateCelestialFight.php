<?php

namespace App\Game\Battle\Events;

use App\Flare\Models\CelestialFight;
use App\Flare\ServerFight\MonsterPlayerFight;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UpdateCelestialFight implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array
     */
    public array $data = [];

    /**
     * Create a new event instance.
     *
     * @param string $characterName
     * @param MonsterPlayerFight|null $celestialFight
     */
    public function __construct(string $characterName, ?MonsterPlayerFight $celestialFight = null) {
        $this->data = [
            'monster_current_health' => is_null($celestialFight) ? 0 : $celestialFight->getMonsterHealth(),
            'celestial_fight_over'   => is_null($celestialFight),
            'who_killed'             => [
                'message' => 'Processing Fight',
                'type'    => 'player-actions',
            ]
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array {
        return new PresenceChannel('celestial-fight-changes');
    }
}
