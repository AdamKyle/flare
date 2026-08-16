<?php

namespace App\Game\Automation\Delve\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Delve\Requests\DelveExplorationRequest;
use App\Game\Automation\Delve\Services\DelveExplorationAutomationService;
use App\Game\Automation\Delve\Services\DelveStatusService;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Maps\Values\LocationType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DelveExplorationController extends Controller
{
    use ChecksAutomationRestrictions;

    /**
     * @param  DelveExplorationAutomationService  $delveExplorationAutomationService  The Delve automation service.
     * @param  DelveStatusService  $delveStatusService  The Delve status service.
     */
    public function __construct(
        private readonly DelveExplorationAutomationService $delveExplorationAutomationService,
        private readonly DelveStatusService $delveStatusService,
    ) {}

    /**
     * Start Delve automation for the character with the validated request options.
     *
     * @param  DelveExplorationRequest  $request  The validated Delve start request.
     * @param  Character  $character  The character starting Delve.
     * @return JsonResponse The start confirmation or validation error response.
     */
    public function begin(DelveExplorationRequest $request, Character $character): JsonResponse
    {

        if (! AttackType::attackTypeExists($request->attack_type)) {
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
            ->where('type', LocationType::CAVE_OF_MEMORIES->value)
            ->first();

        if (is_null($location)) {
            return response()->json([
                'message' => 'You may only delve in locations that allow such an action child.',
            ], 422);
        }

        $this->delveExplorationAutomationService->beginAutomation($character, $location, $request->all());

        return response()->json([
            'message' => 'Delve has started child. Let us see how long you last shall we? (Max delve time is 8 hours.)',
        ]);
    }

    /**
     * Return the character's current Delve automation status.
     *
     * @param  Character  $character  The character to return status for.
     * @return JsonResponse The current Delve status panel.
     */
    public function status(Character $character): JsonResponse
    {
        return response()->json($this->delveStatusService->statusForCharacter($character));
    }

    /**
     * Return quest item detail for a Delve quest item.
     *
     * @param  Character  $character  The character viewing the item.
     * @param  Item  $item  The quest item to detail.
     * @return JsonResponse The item detail or validation error response.
     */
    public function questItemDetail(Character $character, Item $item): JsonResponse
    {
        if ($item->type !== 'quest') {
            return response()->json(['message' => 'Item is not a quest item.'], 422);
        }

        return response()->json([
            'item' => $this->delveStatusService->questItemDetail($item),
        ]);
    }

    /**
     * Dismiss the character's ended Delve status panel.
     *
     * @param  Character  $character  The character dismissing the panel.
     * @return JsonResponse The updated Delve status panel.
     */
    public function dismiss(Character $character): JsonResponse
    {
        $this->delveStatusService->dismissForCharacter($character);

        event(new UpdateCharacterStatus($character->refresh()));

        return response()->json($this->delveStatusService->statusForCharacter($character));
    }

    /**
     * Stop the character's active Delve automation.
     *
     * @param  Character  $character  The character stopping Delve.
     * @return JsonResponse Empty confirmation response.
     */
    public function stop(Character $character): JsonResponse
    {

        $this->delveExplorationAutomationService->stopExploration($character);

        return response()->json();
    }
}
