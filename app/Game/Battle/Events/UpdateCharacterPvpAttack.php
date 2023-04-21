<?php

namespace App\Game\Battle\Events;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UpdateCharacterPvpAttack implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;

    public array $data;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param array $data
     */
    public function __construct(User $user, array $data) {
       $this->user = $user;
       $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-pvp-attack-' . $this->user->id);
    }
}
