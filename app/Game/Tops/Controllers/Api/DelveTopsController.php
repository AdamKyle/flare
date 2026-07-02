<?php

namespace App\Game\Tops\Controllers\Api;

use App\Flare\Models\DelveExploration;
use App\Game\Tops\Services\DelveTopsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DelveTopsController extends Controller
{
    public function __construct(private readonly DelveTopsService $delveTopsService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->delveTopsService->leaderboard($request));
    }

    public function show(DelveExploration $delveExploration): JsonResponse
    {
        return response()->json($this->delveTopsService->detail($delveExploration));
    }
}
