<?php

namespace App\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\Services\ExplorationLogService;
use App\Game\Automation\Services\ExplorationWarningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExplorationWarningController
{
    public function __construct(
        private readonly ExplorationWarningService $explorationWarningService,
        private readonly ExplorationLogService $explorationLogService,
    ) {}

    public function dismiss(Request $request, Character $character): JsonResponse
    {
        $warningId = $request->has('warning_id') ? $request->integer('warning_id') : null;

        $this->explorationWarningService->dismiss($character, $warningId);

        return response()->json($this->explorationLogService->outputForCharacter($character));
    }

    public function dismissEnded(Character $character): JsonResponse
    {
        $this->explorationLogService->dismissEndedLog($character);

        return response()->json($this->explorationLogService->outputForCharacter($character));
    }
}
