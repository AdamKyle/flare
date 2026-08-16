<?php

namespace App\Game\Automation\Exploration\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\Exploration\Services\ExplorationLogService;
use Illuminate\Http\JsonResponse;

class ExplorationOutputController
{
    /**
     * @param  ExplorationLogService  $explorationLogService  The Exploration log service.
     */
    public function __construct(private readonly ExplorationLogService $explorationLogService) {}

    /**
     * Return the character's current Exploration automation output.
     *
     * @param  Character  $character  The character to return output for.
     * @return JsonResponse The current Exploration output panel.
     */
    public function output(Character $character): JsonResponse
    {
        return response()->json($this->explorationLogService->outputForCharacter($character));
    }
}
