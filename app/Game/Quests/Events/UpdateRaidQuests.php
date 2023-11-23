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

class UpdateRaidQuests implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $raidQuests;

    /**
     * Constructor
     */
    public function __construct(array $raidQuests) {
        $this->raidQuests = $raidQuests;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PresenceChannel('update-raid-quests');
    }
}
