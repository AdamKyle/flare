<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\Character;
use App\Game\Kingdoms\Traits\UnitInMovementFormatter;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateUnitMovementLogs implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, UnitInMovementFormatter;

    public $character;

    public $unitMovement;

    /**
     * Create a new event instance.
     */
    public function __construct(Character $character)
    {
        $this->character = $character;
        $this->unitMovement = $this->format($character->unitMovementQueues()->where('is_moving', true)->get());
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-units-in-movement-'.$this->character->user->id);
    }
}
