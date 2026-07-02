<?php

namespace App\Game\Tops\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Tops\Services\KingdomTopsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KingdomTopsController extends Controller
{
    public function __construct(private readonly KingdomTopsService $kingdomTopsService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->kingdomTopsService->leaderboard($request));
    }

    public function show(Character $character): JsonResponse
    {
        return response()->json($this->kingdomTopsService->detail($character));
    }
}
