<?php

namespace App\Game\Kingdoms\Events;


use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;;
use App\Flare\Models\Character;
use App\Game\Kingdoms\Traits\UnitInMovementFormatter;

class UpdateUnitMovementLogs implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels, UnitInMovementFormatter;

    public $character;

    public $unitMovement;

    /**
     * Create a new event instance.
     *
     * @param Character $character
     */
    public function __construct(Character $character) {
        $this->character    = $character;
        $this->unitMovement = $this->format($character->unitMovementQueues()->where('is_moving', true)->get());
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-units-in-movement-' . $this->character->user->id);
    }
}
