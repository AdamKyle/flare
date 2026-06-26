<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Services\DelveMonitoringService;
use App\Admin\Services\ExplorationMonitoringService;
use App\Admin\Services\FactionLoyaltyMonitoringService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function __construct(
        private readonly ExplorationMonitoringService $explorationMonitoringService,
        private readonly FactionLoyaltyMonitoringService $factionLoyaltyMonitoringService,
        private readonly DelveMonitoringService $delveMonitoringService,
    ) {}

    public function explorationActive(): JsonResponse
    {
        return response()->json($this->explorationMonitoringService->activeCharacters());
    }

    public function explorationLogs(Request $request): JsonResponse
    {
        return response()->json($this->explorationMonitoringService->recentLogs($request));
    }

    public function explorationSummary(Request $request): JsonResponse
    {
        return response()->json($this->explorationMonitoringService->summary($request));
    }

    public function explorationChart(Request $request): JsonResponse
    {
        return response()->json($this->explorationMonitoringService->chart($request));
    }

    public function factionLoyaltyActive(): JsonResponse
    {
        return response()->json($this->factionLoyaltyMonitoringService->activeCharacters());
    }

    public function factionLoyaltyRuns(Request $request): JsonResponse
    {
        return response()->json($this->factionLoyaltyMonitoringService->recentRuns($request));
    }

    public function factionLoyaltySummary(Request $request): JsonResponse
    {
        return response()->json($this->factionLoyaltyMonitoringService->summary($request));
    }

    public function factionLoyaltyChart(Request $request): JsonResponse
    {
        return response()->json($this->factionLoyaltyMonitoringService->chart($request));
    }

    public function delveActive(): JsonResponse
    {
        return response()->json($this->delveMonitoringService->activeCharacters());
    }

    public function delveRuns(Request $request): JsonResponse
    {
        return response()->json($this->delveMonitoringService->recentRuns($request));
    }

    public function delveSummary(Request $request): JsonResponse
    {
        return response()->json($this->delveMonitoringService->summary($request));
    }

    public function delveChart(Request $request): JsonResponse
    {
        return response()->json($this->delveMonitoringService->chart($request));
    }
}
