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
     * @param CelestialFight $celestialFight
     * @param bool $closeCelestialFight
     */
    public function __construct(CelestialFight $celestialFight = null, bool $closeCelestialFight = false) {
        $this->data = [
            'close_fight'            => $closeCelestialFight,
            'monster_current_health' => is_null($celestialFight) ? 0 : $celestialFight->current_health,
        ];
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
