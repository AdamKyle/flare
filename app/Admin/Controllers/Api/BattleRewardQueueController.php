<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Services\BattleRewardQueueAdminService;
use App\Game\BattleRewardProcessing\Services\BattleRewardQueueRepairService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BattleRewardQueueController extends Controller
{
    public function __construct(
        private readonly BattleRewardQueueAdminService $battleRewardQueueAdminService,
        private readonly BattleRewardQueueRepairService $battleRewardQueueRepairService,
    ) {}

    public function summary(): JsonResponse
    {
        return response()->json($this->battleRewardQueueAdminService->summary());
    }

    public function charts(): JsonResponse
    {
        return response()->json([
            'last_hour' => $this->battleRewardQueueAdminService->lastHourChart(),
            'last_7_days' => $this->battleRewardQueueAdminService->chart(7),
            'previous_7_days' => $this->battleRewardQueueAdminService->chart(7, false, true),
        ]);
    }

    public function characters(Request $request): JsonResponse
    {
        return response()->json($this->battleRewardQueueAdminService->characters($request));
    }

    public function characterDetail(Request $request, int $characterId): JsonResponse
    {
        return response()->json([
            'charts' => [
                '1' => $this->battleRewardQueueAdminService->statusBreakdown(
                    new Request(['days' => 1]),
                    $characterId,
                ),
                '7' => $this->battleRewardQueueAdminService->statusBreakdown(
                    new Request(['days' => 7]),
                    $characterId,
                ),
                '14' => $this->battleRewardQueueAdminService->statusBreakdown(
                    new Request(['days' => 14]),
                    $characterId,
                ),
                '30' => $this->battleRewardQueueAdminService->statusBreakdown(
                    new Request(['days' => 30]),
                    $characterId,
                ),
            ],
            'requests' => $this->battleRewardQueueAdminService->requests($request, $characterId),
        ]);
    }

    public function requests(Request $request): JsonResponse
    {
        return response()->json($this->battleRewardQueueAdminService->requests($request));
    }

    public function statusBreakdown(Request $request): JsonResponse
    {
        return response()->json($this->battleRewardQueueAdminService->statusBreakdown($request));
    }

    public function stale(): JsonResponse
    {
        return response()->json($this->battleRewardQueueAdminService->staleQueues());
    }

    public function repairStale(): JsonResponse
    {
        return response()->json($this->battleRewardQueueRepairService->repairStaleQueues());
    }
}
