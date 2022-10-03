<?php

namespace App\Game\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;

class GlobalTimeOut implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param User $user
     */
    private User $user;

    /**
     * @var bool $showTimeOut
     */
    public bool $showTimeOut;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param bool $showTimeOut
     */
    public function __construct(User $user, bool $showTimeOut) {
        $this->user        = $user;
        $this->showTimeOut = $showTimeOut;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array {
        return new PrivateChannel('global-timeout-' . $this->user->id);
    }
}
