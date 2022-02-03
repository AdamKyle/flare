<?php

namespace App\Game\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;

class UpdateBaseCharacterInformation implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param array $character
     */
    public $baseStats;

    /**
     * @var User $user
     *
     */
    private $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     */
    public function __construct(User $user, array $baseStats, ) {
        $this->baseStats = $baseStats;
        $this->user      = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-character-base-stats-' . $this->user->id);
    }
}
