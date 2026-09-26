<?php

namespace App\Game\Automation\Delve\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\Delve\Requests\DelveExplorationRequest;
use App\Game\Automation\Delve\Services\DelveExplorationAutomationService;
use App\Game\Automation\Delve\Services\DelveStartService;
use App\Game\Automation\Delve\Services\DelveStatusService;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DelveExplorationController extends Controller
{
    /**
     * @param DelveExplorationAutomationService $delveExplorationAutomationService
     * @param DelveStartService $delveStartService
     * @param DelveStatusService $delveStatusService
     */
    public function __construct(
        private readonly DelveExplorationAutomationService $delveExplorationAutomationService,
        private readonly DelveStartService $delveStartService,
        private readonly DelveStatusService $delveStatusService,
    ) {}

    /**
     * Start Delve automation for the character with the validated request options.
     *
     * @param DelveExplorationRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function begin(DelveExplorationRequest $request, Character $character): JsonResponse
    {
        $result = $this->delveStartService->startDelve($character, $request->validated());
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Return the character's current Delve automation status.
     *
     * @param Character $character
     * @return JsonResponse
     */
    public function status(Character $character): JsonResponse
    {
        return response()->json($this->delveStatusService->statusForCharacter($character));
    }

    /**
     * Return quest item detail for a Delve quest item.
     *
     * @param Character $character
     * @param Item $item
     * @return JsonResponse
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
     * @param Character $character
     * @return JsonResponse
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
     * @param Character $character
     * @return JsonResponse
     */
    public function stop(Character $character): JsonResponse
    {
        $result = $this->delveExplorationAutomationService->stopExploration($character);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}
