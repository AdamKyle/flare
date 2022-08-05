<?php

namespace App\Game\Battle\Events;

use App\Flare\Models\CelestialFight;
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
     * @param array $logs
     * @param string $characterName
     * @param CelestialFight|null $celestialFight
     */
    public function __construct(string $characterName, CelestialFight $celestialFight = null) {
        $this->data = [
            'monster_current_health' => is_null($celestialFight) ? 0 : $celestialFight->current_health,
            'celestial_fight_over'   => is_null($celestialFight),
        ];

        if (is_null($celestialFight)) {
            $this->data['who_killed'] = [
                'message' => is_null($celestialFight) ? $characterName . ' has slaughtered the feral beast!' : '',
                'type'    => 'enemy-action',
            ];
        }

        if (!is_null($celestialFight)) {
            $this->data['who_killed'] = [
                'message' => 'Slippery bastard got away! ' . $characterName . ' was unable to kill the creature. Quick after it!',
                'type'    => 'enemy-action',
            ];
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PresenceChannel('celestial-fight-changes');
    }
}
