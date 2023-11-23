<?php

namespace App\Game\Quests\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnlockSkillEvent  implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;

    public bool $enable;

    /**
     * Constructor
     *
     * @param User $user
     */
    public function __construct(User $user) {
        $this->user   = $user;
        $this->enable = true;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('unlock-skill-' . $this->user->id);
    }
}
