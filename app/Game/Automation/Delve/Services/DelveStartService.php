<?php

namespace App\Game\Automation\Delve\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Values\LocationType;

class DelveStartService
{
    use ResponseBuilder;

    /**
     * @param DelveExplorationAutomationService $delveExplorationAutomationService
     * @param AutomationRestrictionService $automationRestrictionService
     */
    public function __construct(
        private readonly DelveExplorationAutomationService $delveExplorationAutomationService,
        private readonly AutomationRestrictionService $automationRestrictionService,
    ) {}

    /**
     * Start Delve automation when no automation blocks it, the character stands on a Cave of Shadows, and owns the Delve access item.
     *
     * @param Character $character
     * @param array $params
     * @return array
     */
    public function startDelve(Character $character, array $params): array
    {
        $restriction = $this->automationRestrictionService->blockedContext($character, AutomationRestrictionService::START_DELVE);

        if (! is_null($restriction)) {
            return $this->errorResult($restriction['message']);
        }

        $location = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->where('type', LocationType::CAVE_OF_SHADOWS->value)
            ->first();

        if (is_null($location)) {
            return $this->errorResult('You may only delve in locations that allow such an action child.');
        }

        $delveAccessItem = Item::where('effect', ItemEffectType::DELVE->value)->first();

        if (is_null($delveAccessItem) || ! $character->inventory->slots->contains('item_id', $delveAccessItem->id)) {
            return $this->errorResult('You do not have access to Delve at this location child.');
        }

        $this->delveExplorationAutomationService->beginAutomation($character, $location, $params);

        return $this->successResult([
            'message' => 'Delve has started child. Let us see how long you last shall we? (Max delve time is 8 hours.)',
        ]);
    }
}
