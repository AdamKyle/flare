<?php

namespace App\Game\Battle\Events;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\MonthlyPvpParticipant;
use App\Flare\Values\AutomationType;
use App\Flare\Values\EventType;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UpdateCharacterStatus implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array
     */
    public array $characterStatuses = [];

    private $user;

    /**
     * Create a new event instance.
     *
     * @param Character $character
     */
    public function __construct(Character $character) {
        $character = $character->refresh();
        $this->characterStatuses = [
            'can_attack'              => $character->can_attack,
            'can_attack_again_at'     => $character->can_attack_again_at,
            'can_craft'               => $character->can_craft,
            'can_craft_again_at'      => $character->can_craft_again_at,
            'is_dead'                 => $character->is_dead,
            'is_automation_running'   => $character->currentAutomations()->where('character_id', $character->id)->get()->isNotEmpty(),
            'automation_completed_at' => $this->getTimeLeftOnAutomation($character),
            'is_silenced'             => $character->is_silenced,
            'can_move'                => $character->can_move,
            'can_register_for_pvp'    => !is_null(Event::where('type', EventType::MONTHLY_PVP)->first()) && $character->level >= 301
        ];

        $this->user = $character->user;
    }

    protected function getTimeLeftOnAutomation(Character $character) {
        $automation = $character->currentAutomations()->where('type', AutomationType::EXPLORING)->first();

        if (!is_null($automation)) {
            return now()->diffInSeconds($automation->completed_at);
        }

        return 0;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-character-status-' . $this->user->id);
    }
}
