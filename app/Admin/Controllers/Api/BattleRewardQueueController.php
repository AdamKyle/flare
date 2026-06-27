<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Services\BattleRewardQueueAdminService;
use App\Admin\Transformers\BattleRewardQueueCharacterTransformer;
use App\Admin\Transformers\BattleRewardQueueChartTransformer;
use App\Admin\Transformers\BattleRewardQueueRepairResultTransformer;
use App\Admin\Transformers\BattleRewardQueueRequestTransformer;
use App\Admin\Transformers\BattleRewardQueueStaleQueueTransformer;
use App\Admin\Transformers\BattleRewardQueueStatusBreakdownTransformer;
use App\Admin\Transformers\BattleRewardQueueSummaryTransformer;
use App\Flare\Pagination\Pagination;
use App\Game\BattleRewardProcessing\Services\BattleRewardQueueRepairService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BattleRewardQueueController extends Controller
{
    public function __construct(
        private readonly BattleRewardQueueAdminService $battleRewardQueueAdminService,
        private readonly BattleRewardQueueRepairService $battleRewardQueueRepairService,
        private readonly Pagination $pagination,
        private readonly BattleRewardQueueCharacterTransformer $battleRewardQueueCharacterTransformer,
        private readonly BattleRewardQueueRequestTransformer $battleRewardQueueRequestTransformer,
        private readonly BattleRewardQueueSummaryTransformer $battleRewardQueueSummaryTransformer,
        private readonly BattleRewardQueueChartTransformer $battleRewardQueueChartTransformer,
        private readonly BattleRewardQueueStatusBreakdownTransformer $battleRewardQueueStatusBreakdownTransformer,
        private readonly BattleRewardQueueStaleQueueTransformer $battleRewardQueueStaleQueueTransformer,
        private readonly BattleRewardQueueRepairResultTransformer $battleRewardQueueRepairResultTransformer,
    ) {}

    public function summary(): JsonResponse
    {
        return response()->json(
            $this->battleRewardQueueSummaryTransformer->transform(
                $this->battleRewardQueueAdminService->summary(),
            ),
        );
    }

    public function charts(): JsonResponse
    {
        return response()->json([
            'last_hour' => $this->transformChartRows($this->battleRewardQueueAdminService->lastHourChart()),
            'last_7_days' => $this->transformChartRows($this->battleRewardQueueAdminService->chart(7)),
            'previous_7_days' => $this->transformChartRows($this->battleRewardQueueAdminService->chart(7, false, true)),
        ]);
    }

    public function characters(Request $request): JsonResponse
    {
        return response()->json(
            $this->pagination->transformLengthAwarePaginator(
                $this->battleRewardQueueAdminService->characters($request),
                $this->battleRewardQueueCharacterTransformer,
            ),
        );
    }

    public function characterDetail(Request $request, int $characterId): JsonResponse
    {
        return response()->json([
            'charts' => [
                '1' => $this->transformStatusRows($this->battleRewardQueueAdminService->statusBreakdown(new Request(['days' => 1]), $characterId)),
                '7' => $this->transformStatusRows($this->battleRewardQueueAdminService->statusBreakdown(new Request(['days' => 7]), $characterId)),
                '14' => $this->transformStatusRows($this->battleRewardQueueAdminService->statusBreakdown(new Request(['days' => 14]), $characterId)),
                '30' => $this->transformStatusRows($this->battleRewardQueueAdminService->statusBreakdown(new Request(['days' => 30]), $characterId)),
            ],
            'requests' => $this->pagination->transformLengthAwarePaginator(
                $this->battleRewardQueueAdminService->requests($request, $characterId),
                $this->battleRewardQueueRequestTransformer,
            ),
        ]);
    }

    public function requests(Request $request): JsonResponse
    {
        return response()->json(
            $this->pagination->transformLengthAwarePaginator(
                $this->battleRewardQueueAdminService->requests($request),
                $this->battleRewardQueueRequestTransformer,
            ),
        );
    }

    public function statusBreakdown(Request $request): JsonResponse
    {
        return response()->json(
            $this->transformStatusRows($this->battleRewardQueueAdminService->statusBreakdown($request)),
        );
    }

    public function stale(): JsonResponse
    {
        return response()->json(
            array_map(
                fn (array $queue): array => $this->battleRewardQueueStaleQueueTransformer->transform($queue),
                $this->battleRewardQueueAdminService->staleQueues(),
            ),
        );
    }

    public function repairStale(): JsonResponse
    {
        return response()->json(
            $this->battleRewardQueueRepairResultTransformer->transform(
                $this->battleRewardQueueRepairService->repairStaleQueues(),
            ),
        );
    }

    private function transformChartRows(array $rows): array
    {
        return array_map(fn (array $row): array => $this->battleRewardQueueChartTransformer->transform($row), $rows);
    }

    private function transformStatusRows(array $rows): array
    {
        return array_map(fn (array $row): array => $this->battleRewardQueueStatusBreakdownTransformer->transform($row), $rows);
    }
}
