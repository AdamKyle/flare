<?php

namespace App\Game\Battle\Events;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UpdateCharacterStatus implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array
     */
    public array $data = [];

    private $user;

    /**
     * Create a new event instance.
     *
     * @param CelestialFight $celestialFight
     * @param bool $closeCelestialFight
     */
    public function __construct(Character $character) {
        $this->data = [
            'can_attack'          => $character->can_attack,
            'can_attack_again_at' => $character->can_attack_again_at,
            'can_craft'           => $character->can_craft,
            'can_craft_again_at'  => $character->can_craft_again_at,
            'can_adventure'       => $character->can_adventure,
            'show_message'        => $character->can_attack ? false : true,
            'is_dead'             => $character->is_dead,
            'automation_locked'   => $character->user->can_auto_attack,
        ];

        $this->user = $character->user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-character-status-' . $this->user->id);
    }
}
