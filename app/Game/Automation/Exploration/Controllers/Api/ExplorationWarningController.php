<?php

namespace App\Game\Automation\Exploration\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\Exploration\Services\ExplorationLogService;
use App\Game\Automation\Exploration\Services\ExplorationWarningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExplorationWarningController
{
    /**
     * @param ExplorationWarningService $explorationWarningService The Exploration warning service.
     * @param ExplorationLogService $explorationLogService The Exploration log service.
     */
    public function __construct(
        private readonly ExplorationWarningService $explorationWarningService,
        private readonly ExplorationLogService $explorationLogService,
    ) {}

    /**
     * Dismiss an active Exploration warning for the character.
     *
     * @param Request $request The dismiss request, optionally naming a warning id.
     * @param Character $character The character dismissing the warning.
     * @return JsonResponse The updated Exploration output panel.
     */
    public function dismiss(Request $request, Character $character): JsonResponse
    {
        $warningId = $request->has('warning_id') ? $request->integer('warning_id') : null;

        $this->explorationWarningService->dismiss($character, $warningId);

        return response()->json($this->explorationLogService->outputForCharacter($character));
    }

    /**
     * Dismiss the character's ended Exploration log.
     *
     * @param Character $character The character dismissing the ended log.
     * @return JsonResponse The updated Exploration output panel.
     */
    public function dismissEnded(Character $character): JsonResponse
    {
        $this->explorationLogService->dismissEndedLog($character);

        return response()->json($this->explorationLogService->outputForCharacter($character));
    }
}
