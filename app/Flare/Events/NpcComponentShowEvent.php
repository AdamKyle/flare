<?php

namespace App\Flare\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;

class NpcComponentShowEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param User $user
     */
    public $user;

    /**
     * @var string $componentName
     */
    public $componentName;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param string $componentName
     */
    public function __construct(User $user, string $componentName) {
        $this->user          = $user;
        $this->componentName = $componentName;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('component-show-' . $this->user->id);
    }
}
