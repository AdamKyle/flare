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

class UpdateMonsterList implements ShouldBroadcastNow
{
    use CanPlayerMassEmbezzle, Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    public array $monsters;

    private User $user;

    /**
     * Create a new event instance.
     *
     * @param array $monsters
     * @param User $user
     */
    public function __construct(array $monsters, User $user)
    {
        $this->monsters = $monsters;
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-monsters-list-'.$this->user->id);
    }
}
