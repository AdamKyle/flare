<?php

namespace App\Game\Maps\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Gems\Progression\Services\GemProgressionReadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GemWorldProgressController extends Controller
{
    public function __construct(
        private readonly GemProgressionReadService $gemProgressionReadService,
    ) {}

    /**
     * Return the Character's current Gem progression status.
     */
    public function current(Character $character): JsonResponse
    {
        return response()->json($this->gemProgressionReadService->currentStatus($character), 200);
    }
}
