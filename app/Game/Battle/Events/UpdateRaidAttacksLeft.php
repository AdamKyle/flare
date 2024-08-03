<?php

namespace App\Game\Battle\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateRaidAttacksLeft implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array
     */
    public int $attacksLeft;

    private int $userId;

    /**
     * @param  int  $raidBossHealth
     * @param  User  $user
     */
    public function __construct(int $userId, int $attacksLeft)
    {
        $this->userId = $userId;
        $this->attacksLeft = $attacksLeft;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-raid-attacks-left-'.$this->userId);
    }
}
