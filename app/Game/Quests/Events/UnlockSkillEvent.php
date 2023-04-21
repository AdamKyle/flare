<?php

namespace App\Game\Quests\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;

class UnlockSkillEvent
{
    use SerializesModels;

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
