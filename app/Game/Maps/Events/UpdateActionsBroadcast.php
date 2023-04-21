<?php

namespace App\Game\Maps\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Game\Core\Traits\KingdomCache;
Use App\Flare\Models\User;

class UpdateActionsBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var array $character
     */
    public $character;

    /**
     * @var array $monster
     */
    public $monsters;

    /**
     * @var User $user
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param array $character
     * @param array $monsters
     * @param User $user
     */
    public function __construct(array $character, array $monsters, User $user) {
        $this->character = $character;
        $this->monsters  = $monsters;
        $this->user      = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-map-actions-' . $this->user->id);
    }
}
