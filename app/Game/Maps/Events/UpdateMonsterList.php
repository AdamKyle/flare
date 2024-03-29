<?php

namespace App\Game\Maps\Events;

use App\Game\Core\Traits\KingdomCache;
use App\Game\Maps\Services\Common\CanPlayerMassEmbezzle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;
use App\Flare\Models\Map;

class UpdateMonsterList implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache, CanPlayerMassEmbezzle;

    public array $monsters;

    /**
     * @var User $user
     */

    private User $user;

    /**
     * Create a new event instance.
     *
     * @param Map $map
     * @param User $user
     */
    public function __construct(array $monsters, User $user)
    {
        $this->monsters = $monsters;
        $this->user     = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-monsters-list-' . $this->user->id);
    }
}
