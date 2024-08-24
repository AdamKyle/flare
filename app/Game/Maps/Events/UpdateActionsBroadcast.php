<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateActionsBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    /**
     * @var array
     */
    public $character;

    /**
     * @var array
     */
    public $monsters;

    /**
     * @var User
     */
    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct(array $character, array $monsters, User $user)
    {
        $this->character = $character;
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
        return new PrivateChannel('update-map-actions-'.$this->user->id);
    }
}
