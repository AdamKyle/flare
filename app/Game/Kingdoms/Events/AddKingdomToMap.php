<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AddKingdomToMap implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    private User $user;

    public array $myKingdoms;

    /**
     * Create a new event instance.
     */
    public function __construct(Character $character)
    {
        $this->user = $character->user;
        $this->myKingdoms = $this->getKingdoms($character);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('add-kingdom-to-map-'.$this->user->id);
    }
}
