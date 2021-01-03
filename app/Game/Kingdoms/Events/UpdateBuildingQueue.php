<?php

namespace App\Game\Kingdoms\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UpdateBuildingQueue implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

 
    public $queue;

    /**
     * @var User $users
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param bool $isDead | false
     * @return void
     */
    public function __construct(User $user, Collection $queue)
    {
        $this->user    = $user;
        $this->queue   = $queue;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('building-queue-' . $this->user->id);
    }
}
