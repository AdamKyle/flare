<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Service\KingdomQueueService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class KingdomQueueController extends Controller
{
    private KingdomQueueService $kingdomQueueService;

    public function __construct(KingdomQueueService $kingdomQueueService)
    {
        $this->kingdomQueueService = $kingdomQueueService;
    }

    /**
     * Fetch all kingdom queues.
     */
    public function fetchQueuesForKingdom(Kingdom $kingdom, Character $character): JsonResponse
    {
        return response()->json(
            ['queues' => $this->kingdomQueueService->fetchKingdomQueues($kingdom)]
        );
    }
}
