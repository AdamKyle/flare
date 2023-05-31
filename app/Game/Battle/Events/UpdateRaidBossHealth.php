<?php

namespace App\Game\Battle\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;

class UpdateRaidBossHealth implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array
     */
    public int $raidBossHealth;

    /**
     * @var integer
     */
    public int $raidBossId;

    /**
     * @param integer $raidBossHealth
     * @param User $user
     */
    public function __construct(int $raidBossId, int $raidBossHealth) {
        $this->raidBossId     = $raidBossId;
        $this->raidBossHealth = $raidBossHealth;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('update-raid-boss-health-attack');
    }
}
