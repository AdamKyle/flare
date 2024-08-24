<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\Map;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Maps\Services\Common\CanPlayerMassEmbezzle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateRaidMonsters implements ShouldBroadcastNow
{
    use CanPlayerMassEmbezzle, Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    public array $raidMonsters;

    private User $user;

    /**
     * Create a new event instance.
     *
     * @param  Map  $map
     */
    public function __construct(array $raidMonsters, User $user)
    {
        $this->raidMonsters = $raidMonsters;
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-raid-monsters-list-'.$this->user->id);
    }
}
