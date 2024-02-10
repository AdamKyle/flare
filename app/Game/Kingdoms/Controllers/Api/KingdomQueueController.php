<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Game\Kingdoms\Service\KingdomQueueService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;

class KingdomQueueController extends Controller{

    /**
     * @var KingdomQueueService $kingdomQueueService
     */
    private KingdomQueueService $kingdomQueueService;

    /**
     * @param KingdomQueueService $kingdomQueueService
     */
    public function __construct(KingdomQueueService $kingdomQueueService) {
        $this->kingdomQueueService = $kingdomQueueService;
    }

    /**
     * Fetch all kingdom queues.
     *
     * @param Kingdom $kingdom
     * @param Character $character
     * @return JsonResponse
     */
    public function fetchQueuesForKingdom(Kingdom $kingdom, Character $character): JsonResponse {
        return response()->json(
            ['queues' => $this->kingdomQueueService->fetchKingdomQueues($kingdom)]
        );
    }
}
