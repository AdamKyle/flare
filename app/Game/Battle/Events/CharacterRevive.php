<?php

namespace App\Game\Battle\Events;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\User;
use App\Flare\ServerFight\MonsterPlayerFight;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class CharacterRevive implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    private User $user;

    /**
     * @var int $health
     */
    public int $health;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param int $health
     */
    public function __construct(User $user, int $health) {
        $this->user   = $user;
        $this->health = $health;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn() {
        return new PrivateChannel('character-revive-' . $this->user->id);
    }
}
