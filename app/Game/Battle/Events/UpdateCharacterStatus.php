<?php

namespace App\Game\Battle\Events;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Models\MonthlyPvpParticipant;
use App\Flare\Models\Skill;
use App\Flare\Models\User;
use App\Flare\Values\AutomationType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UpdateCharacterStatus implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array $characterStatuses
     */
    public array $characterStatuses = [];

    /**
     * @var User $user
     */
    private User $user;

    /**
     * Create a new event instance.
     *
     * @param Character $character
     */
    public function __construct(Character $character) {
        $character = $character->refresh();

        $craftEvent = Event::where('current_event_goal_step', GlobalEventSteps::CRAFT)->first();
        $enchantEvent = Event::where('current_event_goal_step', GlobalEventSteps::ENCHANT)->first();
        $showCraftForEvent = false;
        $showEnchantingForEvent = false;

        if (!is_null($craftEvent)) {

            $gameMap = GameMap::where('only_during_event_type', $craftEvent->type)->first();
            $globalEvent = GlobalEventGoal::where('event_type', $craftEvent->type)->first();

            if (!is_null($gameMap) && !is_null($globalEvent)) {
                $showCraftForEvent = $character->map->game_map_id === $gameMap->id &&
                    $globalEvent->total_crafts < $globalEvent->max_crafts;
            }
        }

        if (!is_null($enchantEvent)) {
            $gameMap = GameMap::where('only_during_event_type', $enchantEvent->type)->first();

            $globalEvent = GlobalEventGoal::where('event_type', $enchantEvent->type)->first();

            if (!is_null($gameMap) && !is_null($globalEvent)) {
                $showEnchantingForEvent = $character->map->game_map_id === $gameMap->id && $globalEvent->total_enchants < $globalEvent->max_enchants;
            }

        }

        $this->characterStatuses = [
            'can_attack'                     => $character->can_attack,
            'can_attack_again_at'            => now()->diffInSeconds($character->can_attack_again_at),
            'can_craft'                      => $character->can_craft,
            'can_craft_again_at'             => $character->can_craft_again_at,
            'can_spin'                       => $character->can_spin,
            'can_spin_again_at'              => now()->diffInSeconds($character->can_spin_again_at),
            'can_engage_celestials'          => $character->can_engage_celestials,
            'can_engage_celestials_again_at' => now()->diffInSeconds($character->can_engage_celestials_again_at),
            'is_dead'                        => $character->is_dead,
            'is_automation_running'          => $character->currentAutomations()->where('character_id', $character->id)->get()->isNotEmpty(),
            'automation_completed_at'        => $this->getTimeLeftOnAutomation($character),
            'is_silenced'                    => $character->is_silenced,
            'can_move'                       => $character->can_move,
            'can_register_for_pvp'           => !is_null(Event::where('type', EventType::MONTHLY_PVP)->first()) && $character->level >= 301,
            'killed_in_pvp'                  => $character->killed_in_pvp,
            'is_alchemy_locked'              => $this->isAlchemyLocked($character),
            'show_craft_for_event'           => $showCraftForEvent,
            'show_enchanting_for_event'      => $showEnchantingForEvent,
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

    protected function isAlchemyLocked(Character $character): bool {

        $alchemySkill = Skill::where('character_id', $character->id)
            ->where('game_skill_id', GameSkill::where(
                'type',
                SkillTypeValue::ALCHEMY
            )->first()->id)->first();

        if (is_null($alchemySkill)) {
            return true;
        }

        return $alchemySkill->is_locked;
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
