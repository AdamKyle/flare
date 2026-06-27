<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Services\DelveMonitoringService;
use App\Admin\Services\ExplorationMonitoringService;
use App\Admin\Services\FactionLoyaltyMonitoringService;
use App\Admin\Transformers\DelveActiveCharacterTransformer;
use App\Admin\Transformers\DelveChartTransformer;
use App\Admin\Transformers\DelveRunTransformer;
use App\Admin\Transformers\DelveSummaryTransformer;
use App\Admin\Transformers\ExplorationActiveCharacterTransformer;
use App\Admin\Transformers\ExplorationChartTransformer;
use App\Admin\Transformers\ExplorationLogTransformer;
use App\Admin\Transformers\ExplorationSummaryTransformer;
use App\Admin\Transformers\FactionLoyaltyActiveCharacterTransformer;
use App\Admin\Transformers\FactionLoyaltyChartTransformer;
use App\Admin\Transformers\FactionLoyaltyRunTransformer;
use App\Admin\Transformers\FactionLoyaltySummaryTransformer;
use App\Flare\Pagination\Pagination;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function __construct(
        private readonly ExplorationMonitoringService $explorationMonitoringService,
        private readonly FactionLoyaltyMonitoringService $factionLoyaltyMonitoringService,
        private readonly DelveMonitoringService $delveMonitoringService,
        private readonly Pagination $pagination,
        private readonly ExplorationLogTransformer $explorationLogTransformer,
        private readonly FactionLoyaltyRunTransformer $factionLoyaltyRunTransformer,
        private readonly DelveRunTransformer $delveRunTransformer,
        private readonly ExplorationActiveCharacterTransformer $explorationActiveCharacterTransformer,
        private readonly ExplorationSummaryTransformer $explorationSummaryTransformer,
        private readonly ExplorationChartTransformer $explorationChartTransformer,
        private readonly FactionLoyaltyActiveCharacterTransformer $factionLoyaltyActiveCharacterTransformer,
        private readonly FactionLoyaltySummaryTransformer $factionLoyaltySummaryTransformer,
        private readonly FactionLoyaltyChartTransformer $factionLoyaltyChartTransformer,
        private readonly DelveActiveCharacterTransformer $delveActiveCharacterTransformer,
        private readonly DelveSummaryTransformer $delveSummaryTransformer,
        private readonly DelveChartTransformer $delveChartTransformer,
    ) {}

    public function explorationActive(): JsonResponse
    {
        return response()->json($this->transformRows(
            $this->explorationMonitoringService->activeCharacters(),
            fn (array $row): array => $this->explorationActiveCharacterTransformer->transform($row),
        ));
    }

    public function explorationLogs(Request $request): JsonResponse
    {
        return response()->json(
            $this->pagination->transformLengthAwarePaginator(
                $this->explorationMonitoringService->recentLogs($request),
                $this->explorationLogTransformer,
            ),
        );
    }

    public function explorationSummary(Request $request): JsonResponse
    {
        return response()->json($this->explorationSummaryTransformer->transform($this->explorationMonitoringService->summary($request)));
    }

    public function explorationChart(Request $request): JsonResponse
    {
        return response()->json($this->transformRows(
            $this->explorationMonitoringService->chart($request),
            fn (array $row): array => $this->explorationChartTransformer->transform($row),
        ));
    }

    public function factionLoyaltyActive(): JsonResponse
    {
        return response()->json($this->transformRows(
            $this->factionLoyaltyMonitoringService->activeCharacters(),
            fn (array $row): array => $this->factionLoyaltyActiveCharacterTransformer->transform($row),
        ));
    }

    public function factionLoyaltyRuns(Request $request): JsonResponse
    {
        return response()->json(
            $this->pagination->transformLengthAwarePaginator(
                $this->factionLoyaltyMonitoringService->recentRuns($request),
                $this->factionLoyaltyRunTransformer,
            ),
        );
    }

    public function factionLoyaltySummary(Request $request): JsonResponse
    {
        return response()->json($this->factionLoyaltySummaryTransformer->transform($this->factionLoyaltyMonitoringService->summary($request)));
    }

    public function factionLoyaltyChart(Request $request): JsonResponse
    {
        return response()->json($this->transformRows(
            $this->factionLoyaltyMonitoringService->chart($request),
            fn (array $row): array => $this->factionLoyaltyChartTransformer->transform($row),
        ));
    }

    public function delveActive(): JsonResponse
    {
        return response()->json($this->transformRows(
            $this->delveMonitoringService->activeCharacters(),
            fn (array $row): array => $this->delveActiveCharacterTransformer->transform($row),
        ));
    }

    public function delveRuns(Request $request): JsonResponse
    {
        return response()->json(
            $this->pagination->transformLengthAwarePaginator(
                $this->delveMonitoringService->recentRuns($request),
                $this->delveRunTransformer,
            ),
        );
    }

    public function delveSummary(Request $request): JsonResponse
    {
        return response()->json($this->delveSummaryTransformer->transform($this->delveMonitoringService->summary($request)));
    }

    public function delveChart(Request $request): JsonResponse
    {
        return response()->json($this->transformRows(
            $this->delveMonitoringService->chart($request),
            fn (array $row): array => $this->delveChartTransformer->transform($row),
        ));
    }

    private function transformRows(array $rows, callable $transformer): array
    {
        return array_map($transformer, $rows);
    }
}
