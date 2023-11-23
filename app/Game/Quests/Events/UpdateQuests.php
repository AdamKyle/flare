<?php

namespace App\Game\Quests\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateQuests implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $quests;

    /**
     * Constructor
     */
    public function __construct(array $quests) {
        $this->quests = $quests;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PresenceChannel('update-quests');
    }
}
