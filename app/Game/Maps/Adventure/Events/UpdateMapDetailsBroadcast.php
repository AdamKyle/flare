<?php

namespace App\Game\Maps\Adventure\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;
use App\Flare\Models\Map;

class UpdateMapDetailsBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $map;
    public $portDetails;
    public $adventureDetails;

    private $user;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Map $map, User $user, array $portDetails, array $adventureDetails)
    {
        $this->map              = $map;
        $this->user             = $user;
        $this->portDetails      = $portDetails;
        $this->adventureDetails = $adventureDetails;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-map-' . $this->user->id);
    }
}
