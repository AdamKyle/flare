<?php

namespace App\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\LocationType;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Requests\DelveExplorationRequest;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Automation\Services\DelveExplorationAutomationService;
use App\Game\Automation\Services\DelveStatusService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DelveExplorationController extends Controller
{
    use ChecksAutomationRestrictions;

    public function __construct(
        private readonly DelveExplorationAutomationService $delveExplorationAutomationService,
        private readonly DelveStatusService $delveStatusService,
    ) {}

    public function begin(DelveExplorationRequest $request, Character $character): JsonResponse
    {

        if (! AttackTypeValue::attackTypeExists($request->attack_type)) {
            return response()->json([
                'message' => 'Invalid attack type was selected. Please select from the drop down.',
            ], 422);
        }

        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::START_DELVE);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $location = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->where('type', LocationType::CAVE_OF_MEMORIES)
            ->first();

        if ( is_null($location)) {
            return response()->json([
                'message' => 'You may only delve in locations that allow such an action child.',
            ], 422);
        }

        $this->delveExplorationAutomationService->beginAutomation($character, $location, $request->all());

        return response()->json([
            'message' => 'Delve has started child. Let us see how long you last shall we? (Max delve time is 8 hours.)',
        ]);
    }

    public function status(Character $character): JsonResponse
    {
        return response()->json($this->delveStatusService->statusForCharacter($character));
    }

    public function questItemDetail(Character $character, Item $item): JsonResponse
    {
        if ($item->type !== 'quest') {
            return response()->json(['message' => 'Item is not a quest item.'], 422);
        }

        return response()->json([
            'item' => $this->delveStatusService->questItemDetail($item),
        ]);
    }

    public function stop(Character $character): JsonResponse
    {

        $this->delveExplorationAutomationService->stopExploration($character);

        return response()->json();
    }
}
