<?php

namespace App\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\Services\ExplorationLogService;
use Illuminate\Http\JsonResponse;

class ExplorationOutputController
{
    public function __construct(private readonly ExplorationLogService $explorationLogService) {}

    public function output(Character $character): JsonResponse
    {
        return response()->json($this->explorationLogService->outputForCharacter($character));
    }
}
