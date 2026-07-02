<?php

namespace App\Game\Tops\Controllers\Api;

use App\Flare\Models\ExplorationLog;
use App\Game\Tops\Services\ExplorationTopsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExplorationTopsController extends Controller
{
    public function __construct(private readonly ExplorationTopsService $explorationTopsService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->explorationTopsService->leaderboard($request));
    }

    public function show(ExplorationLog $explorationLog): JsonResponse
    {
        return response()->json($this->explorationTopsService->detail($explorationLog));
    }
}
