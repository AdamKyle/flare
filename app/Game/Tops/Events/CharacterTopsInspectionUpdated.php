<?php

namespace App\Game\Tops\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class CharacterTopsInspectionUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public int $characterId, public array $profile) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('tops-character-inspection-'.$this->characterId);
    }
}
