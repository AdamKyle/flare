<?php

namespace App\Flare\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @codeCoverageIgnore
 */
class NpcComponentShowEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  User  $user
     */
    public $user;

    /**
     * @var string
     */
    public $componentName;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, string $componentName)
    {
        $this->user = $user;
        $this->componentName = $componentName;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('component-show-'.$this->user->id);
    }
}
