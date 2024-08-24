<?php

namespace App\Game\Battle\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateRaidBossHealth implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array
     */
    public int $raidBossHealth;

    public int $raidBossId;

    /**
     * @param  User  $user
     */
    public function __construct(int $raidBossId, int $raidBossHealth)
    {
        $this->raidBossId = $raidBossId;
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
