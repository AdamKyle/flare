<?php

namespace App\Game\Battle\Events;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Skill;
use App\Flare\Models\User;
use App\Flare\Values\AutomationType;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\LocationType;
use App\Game\Events\Concerns\ShouldShowCraftingEventButton;
use App\Game\Events\Concerns\ShouldShowEnchantingEventButton;
use App\Game\Events\Values\EventType;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateCharacterStatus implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, ShouldShowCraftingEventButton, ShouldShowEnchantingEventButton;

    public array $characterStatuses = [];

    private User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Character $character)
    {
        $character = $character->refresh();

        $this->characterStatuses = [
            'can_attack' => $character->can_attack,
            'can_attack_again_at' => now()->diffInSeconds($character->can_attack_again_at),
            'can_craft' => $character->can_craft,
            'can_craft_again_at' => $character->can_craft_again_at,
            'can_spin' => $character->can_spin,
            'can_spin_again_at' => now()->diffInSeconds($character->can_spin_again_at),
            'can_engage_celestials' => $character->can_engage_celestials,
            'can_engage_celestials_again_at' => now()->diffInSeconds($character->can_engage_celestials_again_at),
            'is_dead' => $character->is_dead,
            'is_automation_running' => $character->currentAutomations()->where('character_id', $character->id)->where('type', AutomationType::EXPLORING)->get()->isNotEmpty(),
            'is_dwelve_running' => $character->currentAutomations()->where('character_id', $character->id)->where('type', AutomationType::DWELVE)->get()->isNotEmpty(),
            'automation_completed_at' => $this->getTimeLeftOnAutomation($character),
            'is_silenced' => $character->is_silenced,
            'can_move' => $character->can_move,
            'is_alchemy_locked' => $this->isAlchemyLocked($character),
            'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
            'show_enchanting_for_event' => $this->shouldShowEnchantingEventButton($character),
            'is_at_dwelve_location' => $this->isAtDwelveLocation($character),
        ];

        $this->user = $character->user;
    }

    private function getTimeLeftOnAutomation(Character $character)
    {
        $automation = $character->currentAutomations()->where('type', AutomationType::EXPLORING)->first();

        if (! is_null($automation)) {
            return now()->diffInSeconds($automation->completed_at);
        }

        return 0;
    }

    private function isAlchemyLocked(Character $character): bool
    {

        $alchemySkill = Skill::where('character_id', $character->id)
            ->where('game_skill_id', GameSkill::where(
                'type',
                SkillTypeValue::ALCHEMY->value
            )->first()->id)->first();

        if (is_null($alchemySkill)) {
            return true;
        }

        return $alchemySkill->is_locked;
    }

    private function isAtDwelveLocation(Character $character): bool {
        $characterMap = $character->map;

        $questItemForDwelveId = Item::where('effect', ItemEffectsValue::DWELVE)->first()->id;

        $location = Location::where('game_map_id', $characterMap->game_map_id)->where('x', $characterMap->character_position_x)->where('y', $characterMap->character_position_y)
            ->where('type', LocationType::CAVE_OF_MEMORIES)->first();

        $characterHasItem = $character->inventory->slots->filter(function($slot) use ($questItemForDwelveId) {
            return $slot->item_id === $questItemForDwelveId;
        })->isNotEmpty();

        return !is_null($location) && $characterHasItem;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-character-status-' . $this->user->id);
    }
}
