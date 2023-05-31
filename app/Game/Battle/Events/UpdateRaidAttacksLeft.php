<?php

namespace App\Game\Battle\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\PrivateChannel;

Use App\Flare\Models\User;

class UpdateRaidAttacksLeft implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array
     */
    public int $attacksLeft;

    /**
     * @var integer
     */
    private int $userId;

    /**
     * @param integer $raidBossHealth
     * @param User $user
     */
    public function __construct(int $userId, int $attacksLeft) {
        $this->userId      = $userId;
        $this->attacksLeft = $attacksLeft;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-raid-attacks-left-' . $this->userId);
    }
}
