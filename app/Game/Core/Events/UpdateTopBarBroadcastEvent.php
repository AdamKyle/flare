<?php

namespace App\Game\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;

class UpdateTopBarBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array $characterSheet
     */
    public $characterSheet;

    /**
     * @var User $user
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param array $characterSheet
     * @param User $user
     * @return void
     */
    public function __construct(array $characterSheet, User $user)
    {
        $this->characterSheet = $characterSheet;
        $this->user           = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-top-bar-' . $this->user->id);
    }
}
