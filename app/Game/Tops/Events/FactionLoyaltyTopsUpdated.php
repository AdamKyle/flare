<?php

namespace App\Game\Tops\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class FactionLoyaltyTopsUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public array $leaderboard) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('tops-faction-loyalty-leaderboard');
    }
}
